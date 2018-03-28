<?php
declare(strict_types=1);

namespace Attlaz\Project;

use Attlaz\Project\Helper\ExecuteTaskHelper;
use Attlaz\Project\Model\JobCommand;
use Attlaz\Project\Model\Log\Processor as LogProcessor;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Attlaz\Project\Serialization\SerializeTaskResult;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Project
{
    private $branchCode;

    private $commands;

    /** @var ContainerInterface */
    private $container;

    private const TASK_PARAM_SHORT = 't';
    private const TASK_PARAM_LONG = 'task';

    /** @var LogProcessor */
    private $logProcessor;
    /** @var LoggerInterface */
    private $logger;

    private $mode = self::MODE_PRODUCTION;

    public const MODE_PRODUCTION = 'production';
    public const MODE_DEVELOP = 'develop';

    public function __construct(string $branchCode, string $definitionsFile = null)
    {
        if (empty($branchCode)) {
            throw new \InvalidArgumentException('Branch code cannot be empty');
        }

        ini_set('memory_limit', '2G');
        date_default_timezone_set('Europe/Brussels');

        $this->branchCode = $branchCode;
        $this->commands = [];

        $this->initDI($definitionsFile);

        $this->logger = $this->getContainer()
                             ->get(LoggerInterface::class);
    }

    public function setMode(string $mode)
    {
        if ($mode !== self::MODE_PRODUCTION && $mode !== self::MODE_DEVELOP) {
            throw new \InvalidArgumentException('Invalid mode "' . $mode . '", must be product or develop');
        }
        $this->mode = $mode;

        if ($this->mode === self::MODE_DEVELOP) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }
    }

    private function initDI(string $definitionsFile = null)
    {
        if (!\is_null($definitionsFile)) {
            if (!\file_exists($definitionsFile) && !\is_readable($definitionsFile)) {
                throw new \Exception('Unable to add definitions, file "' . $definitionsFile . '" is not readable or does not exist');
            }
        }

        /** @var \DI\ContainerBuilder $containerBuilder */
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->addDefinitions(['branchCode' => $this->branchCode]);

        $containerBuilder->addDefinitions(__DIR__ . '/di.php');
//        $containerBuilder->addDefinitions($this->getDefinitions());

        if (!\is_null($definitionsFile)) {
            $containerBuilder->addDefinitions($definitionsFile);
        }

        $this->container = $containerBuilder->build();
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function registerCommand(string $commandName, string $commandClass)
    {
        if (isset($this->commands[$commandName])) {
            throw new \Exception('Command "' . $commandName . '" already defined');
        }
        if (!\is_subclass_of($commandClass, JobCommand::class)) {
            throw new \Exception('Command must extends ' . JobCommand::class . ' class');
        }
        $this->commands[$commandName] = $commandClass;
    }

    public function getCommandNames(): array
    {
        return \array_keys($this->commands);
    }

    public function hasCommand(string $commandName): bool
    {
        return isset($this->commands[$commandName]);
    }

    public function getCommandClass(string $commandName): string
    {
        if (!isset($this->commands[$commandName])) {
            throw new \Exception('Command "' . $commandName . '" not defined');
        }

        return $this->commands[$commandName];
    }

    public function handleRequest(TaskExecutionRequest $taskExecutionRequest = null): void
    {
        $strTaskResult = '';
        try {
            if (\is_null($taskExecutionRequest)) {
                $taskExecutionRequest = $this->getTaskExecutionRequest();
            }

            /** @var \Attlaz\Project\Model\Log\Processor logProcessor */
            $logProcessor = new \Attlaz\Project\Model\Log\Processor();
            $logProcessor->setExecutionId($taskExecutionRequest->getId());
            $this->logger->pushProcessor($logProcessor);

            $taskExecutionResult = $this->executeTask($taskExecutionRequest);

            $cmd = new SerializeTaskResult();
            $strTaskResult = $cmd->__invoke($taskExecutionResult);

            $this->logger->debug('Sending back response: ' . $strTaskResult);

            $this->sendResponse($strTaskResult);
            if ($taskExecutionResult->getSuccess()) {
                exit(0);
            } else {
                //TODO: change exit code based on exception type
                exit(1);
            }
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());
            exit(1);
        }

        // ob_end_flush();

        exit(1);
    }

    private function sendResponse(string $result)
    {
        echo \base64_encode('Result') . ':' . base64_encode($result);
    }

    private function getTaskExecutionRequest(): TaskExecutionRequest
    {
        $strTask = $this->getCLIOption(self::TASK_PARAM_SHORT, self::TASK_PARAM_LONG);

        if (\is_null($strTask)) {
            throw new \Exception('Invalid request: task execution request not defined');
        }

        $strTask = base64_decode($strTask);
        $taskArray = \json_decode($strTask, true);

        return TaskExecutionRequest::fromArray($taskArray);
    }

    private function getCLIOption(string $short, string $long): ?string
    {
        $options = getopt($short . ':');
//        var_dump($options);
//        var_dump($argv);

        if (isset($options[$short])) {
            return (string)$options[$short];
        }
        if (isset($options[$long])) {
            return (string)$options[$long];
        }

        return null;
    }

    private function executeTask(TaskExecutionRequest $task): TaskExecutionResult
    {
        $cmd = new ExecuteTaskHelper($this);

        return $cmd->__invoke($task);
    }

    private function runAsLocal(): bool
    {
        $jetbrains = \getenv('JETBRAINS_REMOTE_RUN');
        if ($jetbrains === '1') {
            return true;
        }

        //TODO: handle local test run in CLI
        return false;
    }
}