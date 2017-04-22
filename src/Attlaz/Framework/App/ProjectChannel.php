<?php
declare(strict_types=1);

namespace Attlaz\Framework\App;

use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Framework\Serialization\DeserializeTaskResult;
use Attlaz\Framework\Serialization\SerializeTask;
use Psr\Log\LoggerInterface;

class ProjectChannel
{
    private $projectEndpointPath;
    private $logger;

    public function __construct(string $projectEndpointPath, LoggerInterface $logger)
    {

        if (!file_exists($projectEndpointPath)) {
            throw new \Exception('Unable to start project: endpoint "' . $projectEndpointPath . '" not found');
        }

        $this->projectEndpointPath = $projectEndpointPath;
        $this->logger = $logger;

        //TODO: ping the endpoint to see if the project is valid, maybe ask the project a version

    }

    public function requestTaskExecution(Task $task): TaskResult
    {

        $cmd = new SerializeTask();
        $strTask = $cmd->__invoke($task);
        $strTask = \base64_encode($strTask);

        $command = 'php ' . $this->projectEndpointPath . ' -t' . $strTask;

        $this->logger->debug('Send: ' . $command);
        exec($command, $out, $ret);

        switch ($ret) {
            case 0:
                $taskResult = $this->parseProjectTaskExecutionOutput($out);
                break;
            default:
                throw  new \Exception('Unknown task execution status "' . $ret . '"');
        }

        return $taskResult;

    }

    private function parseProjectTaskExecutionOutput(array $output): TaskResult
    {

        if (count($output) !== 1) {
            throw new \Exception('Invalid response');
        }

        $strTaskResult = $output[0];
        $strTaskResult = \base64_decode($strTaskResult);

        $cmd = new DeserializeTaskResult();
        $taskResult = $cmd->__invoke($strTaskResult);

        return $taskResult;
    }

}