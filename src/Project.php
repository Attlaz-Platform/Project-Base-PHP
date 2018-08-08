<?php
declare(strict_types=1);

namespace Attlaz\Project;

use Attlaz\Project\App\Config;
use Attlaz\Project\Helper\ExecuteTaskHelper;
use Attlaz\Project\Helper\TaskExecutionRequestHelper;
use Attlaz\Project\Model\Log\Processor;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Attlaz\Project\Serialization\SerializeTaskResult;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class Project
{

    private $commands;

    /** @var ContainerInterface */
    private $container;

    /** @var LoggerInterface */
    private $logger;

    private $startTime;

    /** @var Config */
    private $config;

    /** @deprecated */
    public const MODE_PRODUCTION = 'production';
    /** @deprecated */
    public const MODE_DEVELOP = 'development';

    public function __construct(Config $config = null)
    {
        $this->startTime = \microtime(true);

        if (\is_null($config)) {
            $config = new Config();
        }
        $this->config = $config;

        ini_set('memory_limit', '2G');
        date_default_timezone_set('Europe/Brussels');

        if ($this->config->mode === Config::MODE_DEVELOPMENT) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }

        $this->commands = [];

        $this->initDI($config->definitionsFile);

        $this->logger = $this->getContainer()
                             ->get(LoggerInterface::class);
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

        if ($this->config->mode === Config::MODE_PRODUCTION) {
            $containerBuilder->enableCompilation(BP . '/var/cache');
            // TODO: this doesn't make sense with PHP CLI
            //$containerBuilder->enableDefinitionCache();
        }

        $containerBuilder->addDefinitions([Config::class => $this->config]);

        $containerBuilder->addDefinitions(__DIR__ . '/di.php');
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

}