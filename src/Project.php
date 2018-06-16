<?php
declare(strict_types=1);

namespace Attlaz\Project;

use Attlaz\Project\Helper\ExecuteTaskHelper;
use Attlaz\Project\Helper\TaskExecutionRequestHelper;
use Attlaz\Project\Model\Log\Processor;
use Attlaz\Project\Model\ProjectConfig;
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

    /** @var LoggerInterface */
    private $logger;

    private $mode = self::MODE_PRODUCTION;

    public const MODE_PRODUCTION = 'production';
    public const MODE_DEVELOP = 'develop';
    private $startTime;

    private $config;

    public function __construct(ProjectConfig $config)
    {
        $this->startTime = \microtime(true);

        $this->config = $config;

        ini_set('memory_limit', '2G');
        date_default_timezone_set('Europe/Brussels');

        $this->branchCode = $config->branchCode;
        $this->commands = [];

        $this->setMode($config->mode);

        $this->initDI($config->definitionsFile);

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

        if ($this->mode === self::MODE_PRODUCTION) {
        $containerBuilder->enableCompilation(__DIR__ . '/var/cache');
        }
        $containerBuilder->addDefinitions(['branchCode' => $this->branchCode]);

        $mongoDBConnectionString = 'mongodb://attlaz:s06X07G2aYh3@Attlaz-storage-1,Attlaz-storage-2,Attlaz-storage-3';
        $mongoDBConnectionString = 'mongodb://attlaz:s06X07G2aYh3@159.65.56.165';
        $mongoDBConnectionString = 'mongodb://mongo-admin:UCGJbmQ25Kdx@174.138.6.248';

        $mongoDBUriOptions = [
            'readPreference' => 'nearest',
        ];

        $containerBuilder->addDefinitions(['mongoDBConnectionString' => $mongoDBConnectionString]);
        $containerBuilder->addDefinitions(['mongoDBUriOptions' => $mongoDBUriOptions]);

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
        if (\key_exists($commandName, $this->commands)) {
            throw new \Exception('Command "' . $commandName . '" already defined');
        }
        //TODO: find "faster" way to to this, or only do it when first running or only when command is executed
//        if (!\is_subclass_of($commandClass, JobCommand::class)) {
//            throw new \Exception('Command must extends ' . JobCommand::class . ' class');
//        }
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

    public function handleRequest(TaskExecutionRequest $request = null): void
    {
        echo 'Start handle request: ' . number_format(\microtime(true) - $this->startTime, 4) . ' sec.' . \PHP_EOL;

        try {
            if (\is_null($request)) {
                $request = TaskExecutionRequestHelper::getRequest();
            }

            /** @var \Attlaz\Project\Model\Log\Processor logProcessor */
            $logProcessor = new Processor();
            $logProcessor->setExecutionId($request->getExecutionId());
            $this->logger->pushProcessor($logProcessor);

            $taskExecutionResult = $this->executeTask($request);

            $cmd = new SerializeTaskResult();
            $strTaskResult = $cmd->__invoke($taskExecutionResult);

            $this->logger->debug('Sending back response: ' . $strTaskResult);
            echo 'Complete: ' . number_format(\microtime(true) - $this->startTime, 4) . ' sec.' . \PHP_EOL;
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

//        exit(1);
    }

    private function sendResponse(string $result)
    {
        echo \base64_encode('Result') . ':' . base64_encode($result);
    }

    private function executeTask(TaskExecutionRequest $task): TaskExecutionResult
    {
        $cmd = new ExecuteTaskHelper($this);

        return $cmd->__invoke($task);
    }

//    private function runAsLocal(): bool
//    {
//        $jetbrains = \getenv('JETBRAINS_REMOTE_RUN');
//        if ($jetbrains === '1') {
//            return true;
//        }
//
//        //TODO: handle local test run in CLI
//        return false;
//    }
}