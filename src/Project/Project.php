<?php

declare(strict_types=1);

namespace Attlaz\Project;

use Attlaz\Adapter\Base\Model\Connection\AdapterConnectionPool;
use Attlaz\Client;
use Attlaz\ConnectionPool\ConnectionPool;
use Attlaz\Project\App\Config;
use Attlaz\Project\App\ConfigHelper;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Command\CacheClean;
use Attlaz\Project\Command\CommandManager;
use Attlaz\Project\Command\ConfigList;
use Attlaz\Project\Command\FlowCommandDiscovery;
use Attlaz\Project\Command\ListFlows;
use Attlaz\Project\Command\RequestDeploy;
use Attlaz\Project\Command\RunFlow;
use Attlaz\Project\Command\RunFlowInteractive;
use Attlaz\Project\Command\RunTests;
use Attlaz\Project\Command\SystemSetup;
use Attlaz\Project\Command\SystemStatus;
use Attlaz\Project\DI\AdapterDILoader;
use Attlaz\Project\DI\InternalFactory;
use Attlaz\Project\FlowRun\CLI;
use Attlaz\Project\FlowRun\FPM;
use Attlaz\Project\Helper\Profiler;
use Attlaz\Project\Storage\SimpleCacheAdapter;
use Attlaz\Project\Storage\StorageManager;
use DI\ContainerBuilder;
use Echron\Tools\FileSystem;
use Echron\Tools\Time;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Console\Application;

class Project
{
    private CommandManager $commandManager;
    private ContainerInterface|null $diContainer = null;
    private LoggerInterface $logger;
    private float $startTime;
    private Environment $environment;
    private Profiler $profiler;


    public function __construct(private readonly string $projectRootPath, Environment|null $environment = null)
    {
        $this->profiler = new Profiler();

        $this->profiler->start('Project');
        $this->startTime = \microtime(true);
        if (\is_null($environment)) {
            $environment = new Environment($projectRootPath);
        }
        $this->environment = $environment;

        try {
            $this->profiler->start('Init environment');
            $this->environment->init();
            $this->profiler->finish('Init environment');

            $client = InternalFactory::getClient($this->environment);

            $this->profiler->start('Get logger');
            $this->logger = InternalFactory::getLogger($this->environment, $client);
            $this->profiler->finish('Get logger');

            $this->profiler->start('Init DI');
            $this->initDI($environment->definitionsFile);
            $container = $this->getDIContainer();
            $this->profiler->finish('Init DI');


            $this->profiler->start('Load Config');
            /** @var Config $config */
            $config = $container->get(Config::class);

            if ($this->environment->isInitialized()) {
                $config->loadConfig();
            }
            $this->profiler->finish('Load Config');

            $this->profiler->start('Command discovery');
            if ($this->environment->isInitialized()) {
                $discovery = new FlowCommandDiscovery($this->projectRootPath, $this->logger);
                //Pre fetch commands
                $discovery->getCommands();

                $this->commandManager->initialize($discovery, $this->diContainer, $this->environment, $this->logger);
            }
            $this->profiler->finish('Command discovery');

        } catch (\Exception $ex) {
            throw $ex;
            // throw new \Exception('Unable to start project: ' . $ex->getMessage(), 0, $ex);
        }
        //   echo PHP_EOL . 'Init cli: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;

        // TODO: only show this in debug (local) mode

        $this->profiler->finish('Project');

//        echo implode(PHP_EOL, $this->profiler->debug());
//        die('--');

    }

    public function getDIContainer(): ContainerInterface
    {
        return $this->diContainer;
    }

    public function run(): void
    {
        /** @var Client $attlazClient */
        $attlazClient = $this->diContainer->get(Client::class);
        $environment = $this->environment;
        if (PHP_SAPI === 'cli') {
            /** @var Config $config */
            $config = $this->diContainer->get(Config::class);

            $commandManager = $this->commandManager;

            $cliApplication = new Application('Attlaz CLI');
            $cliApplication->setAutoExit(false);

            $flowRunCliHandler = new CLI($this->commandManager, $attlazClient, $environment, $this->logger);

            $cliApplication->add(new SystemStatus($environment));


            if ($this->environment->isInitialized()) {
                //                $cliStreamHandler = $this->diContainer->get('attlaz_streamhandler');

                //List tasks
                $cliApplication->add(new ListFlows($commandManager));
                //Execute task
                $cmd = new RunFlow($flowRunCliHandler, $attlazClient, $environment, $this->logger);
                $cliApplication->add($cmd);
                //Execute task interactive
                $cmd = new RunFlowInteractive(
                    $flowRunCliHandler,
                    $attlazClient,
                    $commandManager,
                    $environment,
                    $this->logger
                );
                $cliApplication->add($cmd);
                //Config list
                $cliApplication->add(new ConfigList($config, $environment, $attlazClient, $this->logger));
                //Clean cache
                /** @var StorageManager $storageManager */
                $storageManager = $this->diContainer->get(StorageManager::class);
                $clearCacheCommand = new CacheClean($config, $storageManager, $this->logger);
                $cliApplication->add($clearCacheCommand);
                //Request deploy
                $cliApplication->add(new RequestDeploy($environment, $attlazClient, $this->logger));
                //Run tests
                $cliApplication->add(new RunTests($this->logger));
            } else {
                $cliApplication->add(new SystemSetup($attlazClient, $environment, $this->logger));
            }
            $output = $cliApplication->run();

            // TODO: only show this in debug (local) mode
            $this->logger->debug('Run time: ' . Time::readableSeconds(\microtime(true) - $this->startTime, true));

            if ($output === 0) {
                exit(0);
            }

//TODO: change exit code based on exception type
            exit(1);
        }

        $fpm = new FPM($this->commandManager, $attlazClient, $environment, $this->logger);
        $fpm->run();
    }

    private function initDI(string|null $definitionsFile = null)
    {
        if (!\is_null($definitionsFile) && !\file_exists($definitionsFile) && !\is_readable($definitionsFile)) {
            $strErrorMessage = 'Unable to add definitions, ';
            $strErrorMessage .= 'file "' . $definitionsFile . '" is not readable or does not exist';
            throw new \Exception($strErrorMessage);
        }

        $containerBuilder = new ContainerBuilder();
        //$containerBuilder->useAnnotations(true);
        if ($this->environment->compileDi) {
            $compilationCacheDir = FileSystem::joinPath($this->projectRootPath, 'var', 'cache');

            $containerBuilder->enableCompilation($compilationCacheDir);
            // TODO: this doesn't make sense with PHP CLI
            $containerBuilder->enableDefinitionCache();
        }

        $client = InternalFactory::getClient($this->environment);

        $storageManager = new StorageManager($client, $this->environment);

        $configHelper = new ConfigHelper($this->logger);

        $config = new Config($storageManager, $client, $this->environment, $configHelper);

        $localDefinitions = [
            LoggerInterface::class => $this->logger,
            Environment::class => $this->environment,
            Config::class => $config,
            StorageManager::class => $storageManager,
            CacheInterface::class => new SimpleCacheAdapter($storageManager->cache),
            Client::class => $client,
            AdapterConnectionPool::class => \DI\autowire(ConnectionPool::class),
        ];

        $containerBuilder->addDefinitions($localDefinitions);

        /** Add adapter definitions */
        $adapterHelper = new AdapterDILoader();
        $adapterHelper->addDefinitionsToDi($containerBuilder, $config);

        /** Add project definitions */
        if (!\is_null($definitionsFile)) {
            $containerBuilder->addDefinitions($definitionsFile);
        }

        $this->commandManager = new CommandManager();
        $containerBuilder->addDefinitions([CommandManager::class => $this->commandManager]);

        $containerBuilder->useAutowiring(true);

        $this->diContainer = $containerBuilder->build();
    }

//    protected function executeTaskExecutionRequest(FlowRunRequest $taskExecutionRequest): int
//    {
//        $taskExecutionResult = $this->commandManager->runFlow($taskExecutionRequest);
//
//        //$this->sendResponse($taskExecutionResult);
//
//        if ($taskExecutionResult->getSuccess()) {
//            return 0;
//        } else {
//            //TODO: change exit code based on exception type
//            return 1;
//        }
//    }
}
