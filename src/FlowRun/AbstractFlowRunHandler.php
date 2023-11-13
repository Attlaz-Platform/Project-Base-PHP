<?php

declare(strict_types=1);

namespace Attlaz\Project\FlowRun;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Command\CommandManager;
use Attlaz\Project\Model\FlowRunRequest;
use Attlaz\Project\Model\FlowRunResult;
use Attlaz\Project\Serialization\SerializeFlowRunResult;
use Psr\Log\LoggerInterface;
use function Safe\fwrite;

class AbstractFlowRunHandler
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
    )
    {
        $this->commandManager = $commandManager;
        $this->client = $client;
        $this->environment = $environment;
        $this->logger = $logger;
    }

    public function execute(FlowRunRequest $flowRunRequest): int
    {
        if ($this->environment->getProjectEnvironment()->isLocal) {
            $this->client->getFlowEndpoint()->updateFlowRun($flowRunRequest->getRunId(), 'Running');
        }

        $flowRunResult = $this->commandManager->runFlow($flowRunRequest);

        $this->sendResponse($flowRunResult);

        if ($flowRunResult->getSuccess()) {
            if ($this->environment->getProjectEnvironment()->isLocal) {
                $this->client->getFlowEndpoint()->updateFlowRun($flowRunRequest->getRunId(), 'Complete');
            }

            return 0;
        } else {
            if ($this->environment->getProjectEnvironment()->isLocal) {
                $this->client->getFlowEndpoint()->updateFlowRun($flowRunRequest->getRunId(), 'Failed');
            }

            //TODO: change exit code based on exception type
            return 1;
        }
    }

    protected function sendResponse(FlowRunResult $flowRunResult)
    {
        //TODO: this can be string since CLI is just for debugging purpose
        $cmd = new SerializeFlowRunResult();
        $strFlowRunResult = $cmd->__invoke($flowRunResult);

        $this->logger->debug('Sending back response: ' . \substr($strFlowRunResult, 0, 5000));

        // echo \base64_encode('<result>') . ':' . base64_encode($strTaskResult) . \base64_encode('</result>');
        $response = base64_encode($strFlowRunResult);

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
