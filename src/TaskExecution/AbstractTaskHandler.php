<?php

declare(strict_types=1);

namespace Attlaz\Project\TaskExecution;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Command\CommandManager;
use Attlaz\Project\Model\FlowRunRequest;
use Attlaz\Project\Model\FlowRunResult;
use Attlaz\Project\Serialization\SerializeFlowRunResult;
use Psr\Log\LoggerInterface;

use function Safe\fwrite;

class AbstractTaskHandler
{
    protected CommandManager $commandManager;
    protected Client $client;
    protected Environment $environment;
    protected LoggerInterface $logger;

    public function __construct(
        CommandManager  $commandManager,
        Client          $client,
        Environment     $environment,
        LoggerInterface $logger
    ) {
        $this->commandManager = $commandManager;
        $this->client = $client;
        $this->environment = $environment;
        $this->logger = $logger;
    }

    public function execute(FlowRunRequest $taskExecutionRequest): int
    {
        if ($this->environment->getProjectEnvironment()->isLocal) {
            $this->client->getFlowEndpoint()->updateFlowRun($taskExecutionRequest->getRunId(), 'Running');
        }

        $taskExecutionResult = $this->commandManager->runFlow($taskExecutionRequest);

        $this->sendResponse($taskExecutionResult);

        if ($taskExecutionResult->getSuccess()) {
            if ($this->environment->getProjectEnvironment()->isLocal) {
                $this->client->getFlowEndpoint()->updateFlowRun($taskExecutionRequest->getRunId(), 'Complete');
            }

            return 0;
        } else {
            if ($this->environment->getProjectEnvironment()->isLocal) {
                $this->client->getFlowEndpoint()->updateFlowRun($taskExecutionRequest->getRunId(), 'Failed');
            }

            //TODO: change exit code based on exception type
            return 1;
        }
    }

    protected function sendResponse(FlowRunResult $taskExecutionResult)
    {
        //TODO: this can be string since CLI is just for debugging purpose
        $cmd = new SerializeFlowRunResult();
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
        fwrite(\STDOUT, $output . \PHP_EOL);
    }
}
