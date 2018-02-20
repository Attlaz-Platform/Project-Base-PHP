<?php
declare(strict_types=1);

namespace Attlaz\Project;

use Attlaz\Project\App\Logger;
use Attlaz\Project\Helper\ExecuteTaskHelper;
use Attlaz\Project\Model\Cache\FailOverCachePool;
use Attlaz\Project\Model\JobCommand;
use Attlaz\Project\Model\Log\Processor as LogProcessor;
use Attlaz\Project\Model\Task;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Attlaz\Project\Serialization\SerializeTaskResult;
use Cache\Adapter\PHPArray\ArrayCachePool;
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

        $containerBuilder->addDefinitions($this->getDefinitions());

        if (!\is_null($definitionsFile)) {
            $containerBuilder->addDefinitions($definitionsFile);
        }

        $this->container = $containerBuilder->build();
    }

    private function getDefinitions(): array
    {
        return [
            \Psr\Log\LoggerInterface::class        => \DI\factory(function () {
                $logger = new Logger("Attlaz Project " . $this->branchCode);

                $this->logProcessor = new LogProcessor();
                $logger->pushProcessor($this->logProcessor);

                $format = 'LOG_%level_name%: %message% %context% %extra% [%datetime%]' . \PHP_EOL;

                $formatter = new \Bramus\Monolog\Formatter\ColoredLineFormatter(null, $format);
//        $formatter = new \Monolog\Formatter\LineFormatter($format);
                $formatter->allowInlineLineBreaks(true);
                $formatter->includeStacktraces(true);

                $streamHandler = new \Monolog\Handler\StreamHandler(STDOUT, \Monolog\Logger::DEBUG);
                $streamHandler->setFormatter($formatter);

                $logger->pushHandler($streamHandler);

                /**
                 * Log to MongoDB
                 */
                $mongoDBClient = new \MongoDB\Client('mongodb://hq.attlaz.com');

                $mongoDBHandler = new \Monolog\Handler\MongoDBHandler($mongoDBClient, 'attlaz', 'log');

                $logger->pushHandler($mongoDBHandler);

                \Monolog\ErrorHandler::register($logger);

                return $logger;
            }),
            \Psr\SimpleCache\CacheInterface::class => \DI\factory(function (\Psr\Log\LoggerInterface $logger) {
                //$cache = new \Cache\Adapter\PHPArray\ArrayCachePool();

                $manager = new \MongoDB\Driver\Manager('mongodb://hq.attlaz.com');

                $collection = new \MongoDB\Collection($manager, 'attlaz', $this->branchCode . '_cache');

                $cachePools = [];

                $mongoDBCache = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);
                $mongoDBCache->setLogger($logger);
                $cachePools[] = $mongoDBCache;

                $fileCache = new ArrayCachePool(null);
                $cachePools[] = $fileCache;

                $cache = new FailOverCachePool($cachePools, [
                    'skip_on_failure'        => true,
                    'remove_pool_on_failure' => true,
                ]);
                $cache->setLogger($logger);

                return $cache;
                // $cache = new \League\Flysystem\Adapter\NullAdapter();
            }),
            \Echron\IO\Client\Cache::class         => \DI\factory(function () {
                $manager = new \MongoDB\Driver\Manager('mongodb://hq.attlaz.com');

                $collection = new \MongoDB\Collection($manager, 'attlaz', $this->branchCode . '_storage');

//$collection = \Cache\Adapter\MongoDB\MongoDBCachePool::createCollection($manager, '178.117.199.50:27017', 'attlaz.test');

//var_dump($collection);
//die();
                $pool = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);

                $cacheClient = new \Echron\IO\Client\Cache($pool);

                return $cacheClient;
            }),

        ];
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

    public function handleRequest(TaskExecutionRequest $taskExecutionRequest = null): string
    {
//        \ob_start(function ($buffer) {
//            $this->logger->info('[Unregistered output] ' . $buffer);
//        });

        $strTaskResult = '';
        try {
            if (\is_null($taskExecutionRequest)) {
                $taskExecutionRequest = $this->getTaskExecutionRequest();
            }

            $this->logProcessor->setExecutionId($taskExecutionRequest->getId());

            $taskExecutionResult = $this->executeTask($taskExecutionRequest->getTask());

            $cmd = new SerializeTaskResult();
            $strTaskResult = $cmd->__invoke($taskExecutionResult);

            $this->logger->debug('Sending back response: ' . $strTaskResult);
            $strTaskResult = base64_encode($strTaskResult);

            echo $strTaskResult;
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

    private function executeTask(Task $task): TaskExecutionResult
    {
        $cmd = new ExecuteTaskHelper($this);

        return $cmd->__invoke($task);
    }
}