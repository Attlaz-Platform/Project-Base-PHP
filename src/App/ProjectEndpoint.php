<?php

namespace Attlaz\Framework\App;

use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Framework\Serialization\DeserializeTaskFromString;
use Attlaz\Framework\Serialization\SerializeTaskResult;
use Attlaz\Worker\Helper\ExecuteTaskHelper;
use Psr\Log\LoggerInterface;

class ProjectEndpoint
{
    private $project;
    private $logger;

    private const TASK_PARAM_SHORT = 't';
    private const TASK_PARAM_LONG = 'task';

    public function __construct(Project $project, bool $isCalledByWorker = true)
    {
        $this->project = $project;

        $this->logger = $this->project->getContainer()
                                      ->get(LoggerInterface::class);
    }

    private function handleBuffer(string $buffer)
    {
        $this->logger->info('[Unregistered output] ' . $buffer);
    }

    public function handleRequest(Task $task = null): string
    {
        \ob_start([
            &$this,
            'handleBuffer',
        ], 2);
        $strTaskResult = '';
        try {
            if (\is_null($task)) {
                $task = $this->getTask();
            }

            $result = $this->executeTask($task);

            $cmd = new SerializeTaskResult();
            $strTaskResult = $cmd->__invoke($result);

            $this->logger->debug('Sending back response: ' . $strTaskResult);
            $strTaskResult = base64_encode($strTaskResult);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());
        }

        ob_end_flush();

        echo $strTaskResult;
        exit(0);
    }

    private function getTask(): Task
    {
        $strTask = $this->getCLIOption(self::TASK_PARAM_SHORT, self::TASK_PARAM_LONG);

        if (\is_null($strTask)) {
            throw new \Exception('Invalid request: task not defined');
        }

        $strTask = base64_decode($strTask);

        $cmd = new DeserializeTaskFromString();
        $task = $cmd->__invoke($strTask);

        return $task;
    }

    private function getCLIOption(string $short, string $long): ?string
    {
        $options = getopt($short, [$long]);

        if (isset($options[$short])) {
            return $options[$short];
        }
        if (isset($options[$long])) {
            return $options[$long];
        }

        return null;
    }

    private function executeTask(Task $task): TaskResult
    {
        $cmd = new ExecuteTaskHelper($this->project, $this->logger);

        return $cmd->__invoke($task);
    }
}