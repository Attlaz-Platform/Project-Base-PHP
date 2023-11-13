<?php

declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\FlowRun\AbstractFlowRunHandler;
use Attlaz\Project\Model\FlowRunRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function Safe\base64_decode;
use function Safe\json_decode;

class RunFlow extends Command
{
    private const ARG_FLOW_ID = 'flow';
    private const ARG_ARGUMENTS = 'arguments';
    private const ARG_RUN_ID = 'execution';

    public function __construct(
        protected AbstractFlowRunHandler $flowRunHandler,
        protected Client                 $client,
        protected Environment            $environment,
        protected LoggerInterface        $logger
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('flow:run')
            ->setDescription('Run flow.')
            ->setHelp('This command allows you to run a flow')
            ->addArgument(self::ARG_FLOW_ID, InputArgument::REQUIRED, 'Flow identifier to execute')
            ->addOption(self::ARG_ARGUMENTS, null, InputOption::VALUE_REQUIRED, 'Pass arguments in base64 encoded JSON format', null)
            ->addOption(self::ARG_RUN_ID, null, InputOption::VALUE_REQUIRED, 'Pass the flow run id', null);
    }

    protected function init(InputInterface $input): void
    {
        if ($input->getOption('verbose') === true) {
            // TODO: fix implementation
            //            $this->streamHandler->setLevel(LogLevel::DEBUG);
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input);

        try {
            $flowRunRequest = $this->getRequestFromInput($input);

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

        if (\is_null($flowRunId)) {
            if ($this->environment->getProjectEnvironment()->isLocal) {
                //TODO: only when local and no execution is given
                $projectEnvironmentId = $this->environment->getProjectEnvironment()->id;
                $flowRunId = $this->client->getFlowEndpoint()->createFlowRun($flowId, $projectEnvironmentId);
            } else {
                throw new \Exception('Execution must be defined or environment should be local');
            }
        } else {
            if ($this->areArgumentsInStorage($arguments)) {
                $arguments = $this->getArgumentsFromStorage($flowRunId);
            }
        }

        return new FlowRunRequest($flowId, $arguments, $flowRunId);
    }

    /**
     * @param string $flowRunId
     * @return array
     * @throws \Safe\Exceptions\JsonException
     */
    private function getArgumentsFromStorage(string $flowRunId): array
    {
        $flowRun = $this->client->getFlowEndpoint()->getFlowRun($flowRunId);
        if (\is_null($flowRun)) {
            throw new \Exception('Unable to start flow run: unable to get arguments from storage');
        }

        $arguments = $flowRun['arguments'];
        return json_decode($arguments, true);
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
