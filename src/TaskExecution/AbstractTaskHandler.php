<?php
declare(strict_types=1);

namespace Attlaz\Project\TaskExecution;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Command\CommandManager;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Attlaz\Project\Serialization\SerializeTaskResult;
use Psr\Log\LoggerInterface;

class AbstractTaskHandler
{
    protected $commandManager;
    protected $client;
    protected $environment;
    protected $logger;

    public function __construct(
        CommandManager $commandManager,
        Client $client,
        Environment $environment,
        LoggerInterface $logger
    ) {
        $this->commandManager = $commandManager;
        $this->client = $client;
        $this->environment = $environment;
        $this->logger = $logger;
    }

    public function execute(TaskExecutionRequest $taskExecutionRequest): int
    {
        if ($this->environment->getProjectEnvironment()->isLocal) {
            $this->client->updateTaskExecution($taskExecutionRequest->getExecutionId(), 'Running');
        }

        $taskExecutionResult = $this->commandManager->executeTask($taskExecutionRequest);

        $this->sendResponse($taskExecutionResult);

        if ($taskExecutionResult->getSuccess()) {
            if ($this->environment->getProjectEnvironment()->isLocal) {
                $this->client->updateTaskExecution($taskExecutionRequest->getExecutionId(), 'Complete');
            }

            return 0;
        } else {
            if ($this->environment->getProjectEnvironment()->isLocal) {
                $this->client->updateTaskExecution($taskExecutionRequest->getExecutionId(), 'Failed');
            }

            //TODO: change exit code based on exception type
            return 1;
        }
    }

    protected function sendResponse(TaskExecutionResult $taskExecutionResult)
    {
        //TODO: this can be string since CLI is just for debugging purpose
        $cmd = new SerializeTaskResult();
        $strTaskResult = $cmd->__invoke($taskExecutionResult);

        $this->logger->debug('Sending back response: ' . \substr($strTaskResult, 0, 5000));

        // echo \base64_encode('<result>') . ':' . base64_encode($strTaskResult) . \base64_encode('</result>');
        $response = base64_encode($strTaskResult);

        $responseParts = \str_split($response, 50000);

        $this->output('<response>');
        foreach ($responseParts as $responsePart) {
            $this->output($responsePart);
        }
        $this->output('</response>');
    }

    private function output(string $output): void
    {
        \fwrite(\STDOUT, $output . \PHP_EOL);
    }
}
