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

    private $isCalledByWorker = false;

    public function __construct(Project $project)
    {

        $this->project = $project;

        $this->logger = $this->project->getContainer()
                                      ->get(LoggerInterface::class);

        if ($this->isCalledByWorker) {
            \ob_start(function ($buffer) {
                $this->logger->info('[Unregistered output] ' . $buffer);
            });
        }

    }

    public function handleRequest(Task $task = null): string
    {

        if (\is_null($task)) {
            $task = $this->getTask();
        }

        $result = $this->executeTask($task);

        $cmd = new SerializeTaskResult();
        $strTaskResult = $cmd->__invoke($result);

        $this->logger->debug('Sending back response: ' . $strTaskResult);
        $strTaskResult = base64_encode($strTaskResult);

        if ($this->isCalledByWorker) {
            ob_end_flush();
        }

        echo $strTaskResult;
        exit(0);
    }

    private function getTask(): Task
    {
        $options = getopt("t:");

        if (!isset($options['t'])) {
            throw new \Exception('Invalid request');
        }

        $this->isCalledByWorker = true;

        $strTask = $options['t'];
        $strTask = base64_decode($strTask);

        $cmd = new DeserializeTaskFromString();
        $task = $cmd->__invoke($strTask);

        return $task;
    }

    private function executeTask(Task $task): TaskResult
    {
        $cmd = new ExecuteTaskHelper($this->project, $this->logger);

        return $cmd->__invoke($task);
    }
}