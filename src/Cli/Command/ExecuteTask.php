<?php

declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Model\FlowRunRequest;
use Attlaz\Project\TaskExecution\AbstractTaskHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteTask extends Command
{
    protected $taskExecutor;
    protected $client;
    protected $environment;
//    protected $streamHandler;
    protected $logger;

    private const ARG_TASK = 'task';
    private const ARG_ARGUMENTS = 'arguments';
    private const ARG_EXECUTION = 'execution';

    public function __construct(
        AbstractTaskHandler $taskExecutor,
        Client              $client,
        Environment         $environment,
//        StreamHandler $streamHandler,
        LoggerInterface     $logger
    )
    {
        parent::__construct();

        $this->taskExecutor = $taskExecutor;
        $this->client = $client;
        $this->environment = $environment;
//        $this->streamHandler = $streamHandler;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('task:execute')
            ->setDescription('Run task.')
            ->setHelp('This command allows you to run a task')
            ->addArgument(self::ARG_TASK, InputArgument::REQUIRED, 'Task identifier to execute')
            ->addOption(self::ARG_ARGUMENTS, null, InputOption::VALUE_REQUIRED, 'Pass arguments in base64 encoded JSON format', null)
            ->addOption(self::ARG_EXECUTION, null, InputOption::VALUE_REQUIRED, 'Pass the task execution id', null);
    }

    protected function init(InputInterface $input)
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

    private function getArgumentsFromStorage(string $taskExecutionId): array
    {
        $taskExecution = $this->client->getFlowEndpoint()->getFlowRun($taskExecutionId);
        if (\is_null($taskExecution)) {
            throw new \Exception('Unable to execute task: unable to get arguments from storage');
        }

        $arguments = $taskExecution['arguments'];
        $arguments = \json_decode($arguments, true);

        return $arguments;
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
                throw new \Exception('Invalid arguments');
            }
            // $arguments = (string)$arguments;

            $arguments = \base64_decode($arguments);
            if ($arguments === false) {
                throw new \Exception('Unable to decode arguments');
            }
            //PHP 7.3 \JSON_THROW_ON_ERROR
            $arguments = \json_decode($arguments, true);
            if ($arguments === false || \is_null($arguments)) {
                throw new \Exception('Unable to decode arguments');
            }
        } catch (\Exception $ex) {
            throw new \Exception('Unable to read task arguments: ' . $ex->getMessage());
        }

        return $arguments;
    }
}
