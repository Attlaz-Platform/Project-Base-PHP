<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Client;
use Attlaz\Model\ProjectEnvironment;
use Attlaz\Project\App\Environment;
use Attlaz\Project\FlowRun\AbstractFlowRunHandler;
use Attlaz\Project\Model\FlowRunRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunFlow extends Command
{
    private const ARG_FLOW_ID = 'flow';
    private const ARG_ARGUMENTS = 'arguments';
    private const ARG_RUN_ID = 'run';
    private const ARG_VERBOSE = 'verbose';

    public function __construct(
        protected AbstractFlowRunHandler $flowRunHandler,
        protected Client                 $client,
        protected Environment            $environment,
        protected LoggerInterface        $logger
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('flow:run')
            ->setDescription('Run flow.')
            ->setHelp('This command allows you to run a flow')
            ->addArgument(self::ARG_FLOW_ID, InputArgument::REQUIRED, 'Flow identifier to execute')
            ->addOption(self::ARG_ARGUMENTS, null, InputOption::VALUE_REQUIRED, 'Pass arguments in base64 encoded JSON format', null)
            ->addOption(self::ARG_RUN_ID, null, InputOption::VALUE_REQUIRED, 'Pass the flow run id', null);
    }


    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $flowRunRequest = $this->getRequestFromInput($input);
            if ($input->getOption(self::ARG_VERBOSE) === true) {
                $flowRunRequest->verboseLogging = true;
            }

            return $this->flowRunHandler->execute($flowRunRequest);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }

    private function getRequestFromInput(InputInterface $input): FlowRunRequest
    {
        $flowId = $input->getArgument(self::ARG_FLOW_ID);
        if (!\is_string($flowId)) {
            throw new \Exception('Invalid flow identifier');
        }
        $arguments = $this->getArguments($input);

        $flowRunId = $this->getFlowRunIdFromInput($input);

        $flowRun = null;
        if (\is_null($flowRunId)) {
            if ($this->environment->getProjectEnvironment()->type === ProjectEnvironment::TYPE_LOCAL) {
                //TODO: only when local and no execution is given
                $projectEnvironmentId = $this->environment->getProjectEnvironment()->id;
                $flowRun = $this->client->getFlowEndpoint()->createFlowRun($flowId, $projectEnvironmentId);
            } else {
                throw new \Exception('Execution must be defined or environment should be local');
            }
        } else {
            $flowRun = $this->client->getFlowEndpoint()->getFlowRun($flowRunId);
            $arguments = $flowRun->arguments;
            //  $arguments = $this->getArgumentsFromStorage($flowRunId);

        }
        if ($flowRun === null) {
            // throw error
        }
        return new FlowRunRequest($flowRun, $arguments);
    }

    /**
     * @param string $flowRunId
     * @return array<string, mixed>
     * @throws \JsonException
     */
    private function getArgumentsFromStorage(string $flowRunId): array
    {
        $flowRun = $this->client->getFlowEndpoint()->getFlowRun($flowRunId);
        if (\is_null($flowRun)) {
            throw new \Exception('Unable to start flow run: unable to get arguments from storage');
        }

        $arguments = $flowRun['arguments'];
        if (is_array($arguments)) {
            return $arguments;
        }
        if (is_null($arguments)) {
            return [];
        }
        // TODO: json_decode is no longer needed,
        return json_decode($arguments, true, 512, JSON_THROW_ON_ERROR);
    }

    private function areArgumentsInStorage(array $inputArguments): bool
    {
        return isset($inputArguments['from_storage']);
    }

    private function getFlowRunIdFromInput(InputInterface $input): string|null
    {
        $flowRunId = $input->getOption(self::ARG_RUN_ID);
        if (!\is_null($flowRunId)) {
            if (\is_array($flowRunId)) {
                $flowRunId = $flowRunId[0];
            }

            return (string)$flowRunId;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function getArguments(InputInterface $input): array
    {
        $arguments = $input->getOption(self::ARG_ARGUMENTS);

        if (\is_null($arguments)) {
            return [];
        }

        try {
            if (!\is_string($arguments)) {
                throw new \RuntimeException('Invalid arguments');
            }


            try {
                $arguments = base64_decode($arguments);
            } catch (\Exception $ex) {
                throw new \RuntimeException('Unable to decode arguments');
            }

            try {
                $arguments = json_decode($arguments, true);
            } catch (\Exception $ex) {
                throw new \RuntimeException('Unable to decode arguments');
            }

            if (!is_array($arguments)) {
                throw new \RuntimeException('Invalid arguments');
            }

        } catch (\Exception $ex) {
            throw new \RuntimeException('Unable to read flow arguments: ' . $ex->getMessage());
        }

        return $arguments;
    }
}
