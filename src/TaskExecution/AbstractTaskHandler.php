<?php
declare(strict_types=1);

namespace Attlaz\Project\TaskExecution;

use Attlaz\Project\Command\CommandManager;
use Attlaz\Project\Logger\Logger;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Attlaz\Project\Serialization\SerializeTaskResult;

class AbstractTaskHandler
{
    protected $commandManager;
    protected $logger;

    public function __construct(CommandManager $commandManager, Logger $logger)
    {
        $this->commandManager = $commandManager;
        $this->logger = $logger;
    }

    public function execute(TaskExecutionRequest $taskExecutionRequest): int
    {
        $taskExecutionResult = $this->commandManager->executeTask($taskExecutionRequest);

        $this->sendResponse($taskExecutionResult);

        if ($taskExecutionResult->getSuccess()) {
            return 0;
        } else {
            //TODO: change exit code based on exception type
            return 1;
        }
    }

    protected function sendResponse(TaskExecutionResult $taskExecutionResult)
    {
        //TODO: this can be string since CLI is just for debugging purpose
        $cmd = new SerializeTaskResult();
        $strTaskResult = $cmd->__invoke($taskExecutionResult);

        $this->logger->debug('Sending back response: ' . $strTaskResult);
        echo \base64_encode('Result') . ':' . base64_encode($strTaskResult);
    }
}