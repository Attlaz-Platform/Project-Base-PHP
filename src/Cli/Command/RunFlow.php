<?php

declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Model\FlowRunRequest;
use Attlaz\Project\TaskExecution\AbstractTaskHandler;
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
    private const ARG_TASK = 'task';
    private const ARG_ARGUMENTS = 'arguments';
    private const ARG_EXECUTION = 'execution';

    public function __construct(
        protected AbstractTaskHandler $taskExecutor,
        protected Client              $client,
        protected Environment         $environment,
        protected LoggerInterface     $logger
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('task:execute')
            ->setAliases(['flow:run'])
            ->setDescription('Run flow.')
            ->setHelp('This command allows you to run a flow')
            ->addArgument(self::ARG_TASK, InputArgument::REQUIRED, 'Flow identifier to execute')
            ->addOption(self::ARG_ARGUMENTS, null, InputOption::VALUE_REQUIRED, 'Pass arguments in base64 encoded JSON format', null)
            ->addOption(self::ARG_EXECUTION, null, InputOption::VALUE_REQUIRED, 'Pass the flow run id', null);
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
            $taskExecutionRequest = $this->getRequestFromInput($input);

            return $this->taskExecutor->execute($taskExecutionRequest);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }

    private function getRequestFromInput(InputInterface $input): FlowRunRequest
    {
        $taskId = $input->getArgument(self::ARG_TASK);
        if (!\is_string($taskId)) {
            throw new \Exception('Invalid task identifier');
        }
        $arguments = $this->getArguments($input);

        $taskExecutionId = $this->getTaskExecutionIdFromInput($input);

        if (\is_null($taskExecutionId)) {
            if ($this->environment->getProjectEnvironment()->isLocal) {
                //TODO: only when local and no execution is given
                $projectEnvironmentId = $this->environment->getProjectEnvironment()->id;
                $taskExecutionId = $this->client->getFlowEndpoint()->createFlowRun($taskId, $projectEnvironmentId);
            } else {
                throw new \Exception('Execution must be defined or environment should be local');
            }
        } else {
            if ($this->areArgumentsInStorage($arguments)) {
                $arguments = $this->getArgumentsFromStorage($taskExecutionId);
            }
        }

        return new FlowRunRequest($taskId, $arguments, $taskExecutionId);
    }

    /**
     * @param string $taskExecutionId
     * @return array
     * @throws \Safe\Exceptions\JsonException
     */
    private function getArgumentsFromStorage(string $taskExecutionId): array
    {
        $taskExecution = $this->client->getFlowEndpoint()->getFlowRun($taskExecutionId);
        if (\is_null($taskExecution)) {
            throw new \Exception('Unable to execute task: unable to get arguments from storage');
        }

        $arguments = $taskExecution['arguments'];
        return json_decode($arguments, true);
    }

    private function areArgumentsInStorage(array $inputArguments): bool
    {
        return isset($inputArguments['from_storage']);
    }

    private function getTaskExecutionIdFromInput(InputInterface $input): ?string
    {
        $taskExecutionId = $input->getOption(self::ARG_EXECUTION);
        if (!\is_null($taskExecutionId)) {
            if (\is_array($taskExecutionId)) {
                $taskExecutionId = $taskExecutionId[0];
            }

            return (string)$taskExecutionId;
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
