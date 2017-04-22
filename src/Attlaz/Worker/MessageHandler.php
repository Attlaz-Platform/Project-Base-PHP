<?php
declare(strict_types=1);

namespace Attlaz\Worker;

use Attlaz\Framework\App\ProjectChannel;
use Attlaz\Framework\Helper\DateTimeHelper;
use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Framework\Serialization\DeserializeTaskFromString;
use Psr\Log\LoggerInterface;

class MessageHandler
{
    private $logger;
    private $projectChannel;
    /** @var  callable */
    private $onQuitWorker;

    public function __construct(LoggerInterface $logger, ProjectChannel $projectChannel)
    {
        $this->logger = $logger;
        $this->projectChannel = $projectChannel;
    }

    public function setOnQuitWorker(callable $onQuitWorker)
    {


        $this->onQuitWorker = $onQuitWorker;
    }

    public function handleMessage(string $messageBody): TaskResult
    {
        $this->logger->debug('Incoming message');
        $received = DateTimeHelper::getNow();
        try {
            $task = $this->decodeBodyToTask($messageBody);
            if ($task->getMethod() === 'quit') {

                if (isset($this->onQuitWorker) && \is_callable($this->onQuitWorker)) {

                    $onQuitWorker = $this->onQuitWorker;
                    $onQuitWorker();

                } else {
                    $this->logger->warning('Unable to quit worker: onQuitWorker callback not defined');
                }

                //$this->queue->disconnect();
                $taskResult = new TaskResult($task, '', true);
            } else {
                $taskResult = $this->executeTask($task);
            }

        } catch (\Throwable $ex) {
            $this->logger->error('Unable to process message: ' . $ex->getMessage());
            $taskResult = $this->getErrorTaskResult($ex);
        }

        $responded = DateTimeHelper::getNow();
        $taskResult->setReceived($received);
        $taskResult->setResponded($responded);

        return $taskResult;
    }

    private function decodeBodyToTask(string $body): Task
    {
        return (new DeserializeTaskFromString())($body);
    }

    private function executeTask(Task $task): TaskResult
    {


        $taskResult = $this->projectChannel->requestTaskExecution($task);

        return $taskResult;

    }

    private function getErrorTaskResult(\Throwable $error): TaskResult
    {
        return new TaskResult(new Task('unknown'), 'Unable to execute task: ' . $error->getMessage(), false);
    }

}