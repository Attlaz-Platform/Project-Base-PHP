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

    public function __construct(Project $project)
    {
        $this->project = $project;

        $this->logger = $this->project->getContainer()
                                      ->get(LoggerInterface::class);

    }

    public function handleRequest(): string
    {

        $options = getopt("t:");

        if (!isset($options['t'])) {
            throw new \Exception('Invalid request');
        }
        $strTask = $options['t'];
        $strTask = base64_decode($strTask);

        $cmd = new DeserializeTaskFromString();
        $task = $cmd->__invoke($strTask);

        $result = $this->executeTask($task);

        $cmd = new SerializeTaskResult();
        $strTaskResult = $cmd->__invoke($result);
        $strTaskResult = base64_encode($strTaskResult);

        echo $strTaskResult;
        exit(0);
    }

    private function executeTask(Task $task): TaskResult
    {
        $cmd = new ExecuteTaskHelper($this->project, $this->logger);

        return $cmd->__invoke($task);
    }
}