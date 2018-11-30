<?php
declare(strict_types=1);

namespace Attlaz\Project;

use Attlaz\Project\App\Config;
use Attlaz\Project\Cli\Command\ExecuteTask;
use Attlaz\Project\Cli\Command\ExecuteTaskInteractive;
use Attlaz\Project\Cli\Command\ListTasks;
use Attlaz\Project\Command\CommandDiscovery;
use Attlaz\Project\Command\CommandManager;
use DI\ContainerBuilder;
use Echron\Tools\Time;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;

class Project
{
    /** @var CommandManager */
    private $commandRegistry;

    /** @var ContainerInterface */
    private $diContainer;

    /** @var LoggerInterface */
    private $logger;

    private $startTime;

    /** @var Config */
    private $config;

    private $projectRootPath;

    private $application;

    public function __construct(string $projectRootPath, Config $config = null)
    {
        $this->startTime = \microtime(true);

        $this->projectRootPath = $projectRootPath;
        if (\is_null($config)) {
            $config = new Config($projectRootPath);
        }
        $this->config = $config;

        ini_set('memory_limit', '2G');
        date_default_timezone_set('Europe/Brussels');

        if ($this->config->mode === Config::MODE_DEVELOPMENT) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        }

//        echo PHP_EOL . 'Finish environment ' . Time::readableSeconds(\microtime(true) - $this->startTime) . \PHP_EOL;
//
//        $start = \microtime(true);
        $this->initDI($config->definitionsFile);

//        echo PHP_EOL . 'Init DI: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;
//
//        $start = \microtime(true);
        $this->logger = $this->getDIContainer()
                             ->get(LoggerInterface::class);

//        echo PHP_EOL . 'Get logger: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;
//        $start = \microtime(true);
        $discovery = new CommandDiscovery($this->projectRootPath);
        //Pre fetch commands
        $discovery->getCommands();

        $this->commandRegistry = new CommandManager($discovery, $this->diContainer, $this->logger);

//        echo PHP_EOL . 'Command discovery: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;
//
//        $start = \microtime(true);
        $this->application = new Application();
        $this->application->setAutoExit(false);

        $this->application->add(new ListTasks($this->commandRegistry, $this->logger));
        $this->application->add(new ExecuteTask($this->commandRegistry, $this->logger));
        $this->application->add(new ExecuteTaskInteractive($this->commandRegistry, $this->logger));

//        echo PHP_EOL . 'Init cli: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;
//
//        echo PHP_EOL . 'Constructor Total time: ' . Time::readableSeconds(\microtime(true) - $this->startTime) . \PHP_EOL;
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

        if ($this->config->compileDi) {
            $containerBuilder->enableCompilation($this->projectRootPath . \DIRECTORY_SEPARATOR . 'var' . \DIRECTORY_SEPARATOR . 'cache');
            // TODO: this doesn't make sense with PHP CLI
            //$containerBuilder->enableDefinitionCache();
        }

        $containerBuilder->addDefinitions([Config::class => $this->config]);

        $containerBuilder->addDefinitions(__DIR__ . \DIRECTORY_SEPARATOR . 'di.php');
        if (!\is_null($definitionsFile)) {
            $containerBuilder->addDefinitions($definitionsFile);
        }

        $this->diContainer = $containerBuilder->build();
    }

    public function getDIContainer(): ContainerInterface
    {
        return $this->diContainer;
    }

    public function run()
    {
        $output = $this->application->run();

        // var_dump($output);
        echo PHP_EOL . 'Run time: ' . Time::readableSeconds(\microtime(true) - $this->startTime) . \PHP_EOL;

        if ($output === 0) {
            exit(0);
        } else {
            //TODO: change exit code based on exception type
            exit(1);
        }
    }

//    public function handleRequest(TaskExecutionRequest $taskExecutionRequest = null): void
//    {
//        echo PHP_EOL . 'Start handle request: ' . Time::readableSeconds(\microtime(true) - $this->startTime) . \PHP_EOL;
//
//        try {
//            if (\is_null($taskExecutionRequest)) {
//                $taskExecutionRequest = TaskExecutionRequestHelper::getRequest();
//            }
//
//            /** @var \Attlaz\Project\Logger\Processor logProcessor */
//            $logProcessor = new Processor();
//            $logProcessor->setExecutionId($taskExecutionRequest->getExecutionId());
//            $this->logger->pushProcessor($logProcessor);
//
//            $taskExecutionResult = $this->commandRegistry->executeTask($taskExecutionRequest);
//
//            $cmd = new SerializeTaskResult();
//            $strTaskResult = $cmd->__invoke($taskExecutionResult);
//
//            $this->logger->debug('Sending back response: ' . $strTaskResult);
//
//            $this->sendResponse($strTaskResult);
//
//            echo PHP_EOL . 'Execution time: ' . Time::readableSeconds(\microtime(true) - $this->startTime) . \PHP_EOL;
//
//            if ($taskExecutionResult->getSuccess()) {
//                exit(0);
//            } else {
//                //TODO: change exit code based on exception type
//                exit(1);
//            }
//        } catch (\Throwable $ex) {
//            $this->logger->error($ex->getMessage());
//            exit(1);
//        }
//    }

//    private function sendResponse(string $result)
//    {
//        echo \base64_encode('Result') . ':' . base64_encode($result);
//    }
}
