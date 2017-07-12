<?php
declare(strict_types=1);

namespace Attlaz\Project;

use Attlaz\Project\App\Logger;
use Attlaz\Project\Helper\ExecuteTaskHelper;
use Attlaz\Project\Model\JobCommand;
use Attlaz\Project\Model\Log\Processor as LogProcessor;
use Attlaz\Project\Model\Task;
use Attlaz\Project\Model\TaskResult;
use Attlaz\Project\Serialization\DeserializeTaskFromString;
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

                $cache = new \Cache\Adapter\MongoDB\MongoDBCachePool($collection);
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

    public function handleRequest(Task $task = null): string
    {
//        \ob_start(function ($buffer) {
//            $this->logger->info('[Unregistered output] ' . $buffer);
//        });

        $strTaskResult = '';
        try {
            if (\is_null($task)) {
                $task = $this->getTask();
            }

            $this->logProcessor->setExecutionId($task->getId());

            $result = $this->executeTask($task);

            $cmd = new SerializeTaskResult();
            $strTaskResult = $cmd->__invoke($result);

            $this->logger->debug('Sending back response: ' . $strTaskResult);
            $strTaskResult = base64_encode($strTaskResult);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());
        }

        // ob_end_flush();

        echo $strTaskResult;
        exit(0);
    }

    private function getTask(): Task
    {
        $strTask = $this->getCLIOption(self::TASK_PARAM_SHORT, self::TASK_PARAM_LONG);

        if (\is_null($strTask)) {
            throw new \Exception('Invalid request: task not defined');
        }

        $strTask = base64_decode($strTask);

        $cmd = new DeserializeTaskFromString();
        $task = $cmd->__invoke($strTask);

        return $task;
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

    private function executeTask(Task $task): TaskResult
    {
        $cmd = new ExecuteTaskHelper($this);

        return $cmd->__invoke($task);
    }
}