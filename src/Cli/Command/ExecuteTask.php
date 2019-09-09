<?php
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\TaskExecution\CLI;
use Monolog\Handler\StreamHandler;
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
    protected $streamHandler;
    protected $logger;

    public function __construct(
        CLI $taskExecutor,
        Client $client,
        Environment $environment,
        StreamHandler $streamHandler,
        LoggerInterface $logger
    ) {
        parent::__construct();

        $this->taskExecutor = $taskExecutor;
        $this->client = $client;
        $this->environment = $environment;
        $this->streamHandler = $streamHandler;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('task:execute')
             ->setDescription('Run task.')
             ->setHelp('This command allows you to run a task')
             ->addArgument('task', InputArgument::REQUIRED, 'Task (id) to execute')
             ->addOption('arguments', null, InputOption::VALUE_REQUIRED, '', null)
             ->addOption('execution', null, InputOption::VALUE_REQUIRED, '', null);
    }

    protected function init(InputInterface $input)
    {
        if ($input->getOption('verbose') === true) {
            $this->streamHandler->setLevel(LogLevel::DEBUG);
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

    private function getRequestFromInput(InputInterface $input): TaskExecutionRequest
    {
        $taskId = $input->getArgument('task');
        if (!\is_string($taskId)) {
            throw new \Exception('Invalid task identifier');
        }
        $arguments = $this->getArguments($input);

        $taskExecutionId = $input->getOption('execution');

        if (\is_null($taskExecutionId)) {
            if ($this->environment->getProjectEnvironment()->isLocal) {
                //TODO: only when local and no execution is given
                $projectEnvironmentId = $this->environment->getProjectEnvironment()->id;
                $taskExecutionId = $this->client->createTaskExecution($taskId, $projectEnvironmentId);
            } else {
                throw new \Exception('Execution must be defined or environment should be local');
            }
        } else {
            if ($this->areArgumentsInStorage($arguments)) {
                $arguments = $this->getArgumentsFromStorage($taskExecutionId);
            }
        }

        return new TaskExecutionRequest($taskId, $arguments, $taskExecutionId);
    }

    private function getArgumentsFromStorage(string $taskExecutionId): ?array
    {
        $taskExecution = $this->client->getTaskExecution($taskExecutionId);
        if (!\is_null($taskExecution)) {
            $arguments = $taskExecution['arguments'];
            $arguments = \json_decode($arguments, true);

            return $arguments;
        }

        return null;
    }

    private function areArgumentsInStorage(array $inputArguments): bool
    {
        return isset($inputArguments['from_storage']);
    }

    private function getArguments(InputInterface $input): array
    {
        $arguments = $input->getOption('arguments');

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
