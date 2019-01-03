<?php
declare(strict_types=1);

namespace Attlaz\Project;

use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Cache\CacheManager;
use Attlaz\Project\Cli\Command\CacheClean;
use Attlaz\Project\Cli\Command\ConfigList;
use Attlaz\Project\Cli\Command\ExecuteTask;
use Attlaz\Project\Cli\Command\ExecuteTaskInteractive;
use Attlaz\Project\Cli\Command\ListTasks;
use Attlaz\Project\Cli\Command\RunTests;
use Attlaz\Project\Command\CommandDiscovery;
use Attlaz\Project\Command\CommandManager;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\TaskExecution\CLI;
use Attlaz\Project\TaskExecution\FPM;
use DI\ContainerBuilder;
use Echron\Tools\Time;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;

class Project
{
    /** @var CommandManager */
    private $commandManager;

    /** @var ContainerInterface */
    private $diContainer;

    /** @var LoggerInterface */
    private $logger;

    private $startTime;

    /** @var Environment */
    private $environment;

    private $projectRootPath;

    public function __construct(string $projectRootPath, Environment $environment = null)
    {
        $this->startTime = \microtime(true);

        $this->projectRootPath = $projectRootPath;
        if (\is_null($environment)) {
            $environment = new Environment($projectRootPath);
        }
        $this->environment = $environment;

        try {
            // echo PHP_EOL . 'Finish environment ' . Time::readableSeconds(\microtime(true) - $this->startTime) .
            //   \PHP_EOL;

            $start = \microtime(true);
            $this->initDI($environment->definitionsFile);

            //   echo PHP_EOL . 'Init DI: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;

            $start = \microtime(true);
            $container = $this->getDIContainer();
            $this->logger = $container->get(LoggerInterface::class);

            //  echo PHP_EOL . 'Get logger: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;
            $start = \microtime(true);
            $config = $container->get(Config::class);
            $config->loadConfig();

            //  echo PHP_EOL . 'Get config: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;
            $start = \microtime(true);
            $discovery = new CommandDiscovery($this->projectRootPath);
            //Pre fetch commands
            $discovery->getCommands();

            $this->commandManager = new CommandManager($discovery, $this->diContainer, $this->logger);

            // echo PHP_EOL . 'Command discovery: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;

            $start = \microtime(true);
        } catch (\Exception $ex) {
            throw new \Exception('Unable to start project: ' . $ex->getMessage(), 0, $ex);
        }
        //   echo PHP_EOL . 'Init cli: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;

        echo PHP_EOL . 'Constructor Total time: ' . Time::readableSeconds(\microtime(true) - $this->startTime) . \PHP_EOL;
    }

    private function initDI(string $definitionsFile = null)
    {
        if (!\is_null($definitionsFile) && !\file_exists($definitionsFile) && !\is_readable($definitionsFile)) {
            throw new \Exception('Unable to add definitions, file "' . $definitionsFile . '" is not readable or does not exist');
        }

        /** @var \DI\ContainerBuilder $containerBuilder */
        $containerBuilder = new ContainerBuilder();

        if ($this->environment->compileDi) {
            $containerBuilder->enableCompilation($this->projectRootPath . \DIRECTORY_SEPARATOR . 'var' . \DIRECTORY_SEPARATOR . 'cache');
            // TODO: this doesn't make sense with PHP CLI
            $containerBuilder->enableDefinitionCache();
        }

        $containerBuilder->addDefinitions([Environment::class => $this->environment]);

        //        $config = new Config($container->get(CacheManager::class), $this->environment, $this->logger);
        //        $containerBuilder->addDefinitions([Config::class => $config]);

        $containerBuilder->addDefinitions(__DIR__ . \DIRECTORY_SEPARATOR . 'di.php');
        if (!\is_null($definitionsFile)) {
            $containerBuilder->addDefinitions($definitionsFile);
        }

        $containerBuilder->useAutowiring(true);

        $this->diContainer = $containerBuilder->build();
    }

    public function getDIContainer(): ContainerInterface
    {
        return $this->diContainer;
    }

    public function run()
    {
        if (PHP_SAPI === 'cli') {
            $config = $this->diContainer->get(Config::class);

            $cliApplication = new Application();
            $cliApplication->setAutoExit(false);

            $cli = new CLI($this->commandManager, $this->logger);

            $cliApplication->add(new ListTasks($this->commandManager, $this->logger));
            $cliApplication->add(new ExecuteTask($cli, $this->logger));
            $cliApplication->add(new ExecuteTaskInteractive($cli, $this->commandManager, $this->logger));
            $cliApplication->add(new ConfigList($config, $this->logger));
            $cliApplication->add(new CacheClean($config, $this->diContainer->get(CacheManager::class), $this->logger));
            $cliApplication->add(new RunTests( $this->logger));
            $output = $cliApplication->run();

            echo PHP_EOL . 'Run time: ' . Time::readableSeconds(\microtime(true) - $this->startTime) . \PHP_EOL;

            if ($output === 0) {
                exit(0);
            } else {
                //TODO: change exit code based on exception type
                exit(1);
            }
        } else {
            $fpm = new FPM($this->commandManager, $this->logger);
            $fpm->run();
        }
    }

    protected function executeTaskExecutionRequest(TaskExecutionRequest $taskExecutionRequest): int
    {
        $taskExecutionResult = $this->commandManager->executeTask($taskExecutionRequest);

        //$this->sendResponse($taskExecutionResult);

        if ($taskExecutionResult->getSuccess()) {
            return 0;
        } else {
            //TODO: change exit code based on exception type
            return 1;
        }
    }
}
