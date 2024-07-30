<?php

declare(strict_types=1);

namespace Attlaz\Project;

use Attlaz\Client;
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
    private ContainerInterface $diContainer;
    private LoggerInterface $logger;
    private float $startTime;
    private Environment $environment;


    public function __construct(private readonly string $projectRootPath, Environment $environment = null)
    {
        $this->startTime = \microtime(true);
        if (\is_null($environment)) {
            $environment = new Environment($projectRootPath);
        }
        $this->environment = $environment;

        try {
            $this->environment->init();

            $client = InternalFactory::getClient($this->environment);
            $this->logger = InternalFactory::getLogger($this->environment, $client);
            // echo PHP_EOL . 'Finish environment ' . Time::readableSeconds(\microtime(true) - $this->startTime) .
            //   \PHP_EOL;

            //            $start = \microtime(true);
            $this->initDI($environment->definitionsFile);

            //   echo PHP_EOL . 'Init DI: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;

            $start = \microtime(true);
            $container = $this->getDIContainer();
            //            $this->logger = $container->get(LoggerInterface::class);

            //  echo PHP_EOL . 'Get logger: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;
            //            $start = \microtime(true);
            /** @var Config $config */
            $config = $container->get(Config::class);

            if ($this->environment->isInitialized()) {
                $config->loadConfig();
            }

            //  echo PHP_EOL . 'Get config: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;
            // $start = \microtime(true);
            if ($this->environment->isInitialized()) {
                $discovery = new FlowCommandDiscovery($this->projectRootPath);
                //Pre fetch commands
                $discovery->getCommands();

                $this->commandManager->initialize($discovery, $this->diContainer, $this->environment, $this->logger);
            }

            // echo PHP_EOL . 'Command discovery: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;

            $start = \microtime(true);
        } catch (\Exception $ex) {
            throw new \Exception('Unable to start project: ' . $ex->getMessage(), 0, $ex);
        }
        //   echo PHP_EOL . 'Init cli: ' . Time::readableSeconds(\microtime(true) - $start) . \PHP_EOL;

        // TODO: only show this in debug (local) mode

        $this->logger->debug('Constructor Total time: ' . Time::readableSeconds(\microtime(true) - $this->startTime));

    }

    private function initDI(string $definitionsFile = null)
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
        ];

        $containerBuilder->addDefinitions($localDefinitions);


        //        $containerBuilder->addDefinitions(__DIR__ . \DIRECTORY_SEPARATOR . 'di.php');
        if (!\is_null($definitionsFile)) {
            $containerBuilder->addDefinitions($definitionsFile);
        }

        //  $config = $containerBuilder->get(Config::class);

        //        $discovery = new CommandDiscovery($this->projectRootPath);
        //        //Pre fetch commands
        //        $discovery->getCommands();

        $this->commandManager = new CommandManager();
        $containerBuilder->addDefinitions([CommandManager::class => $this->commandManager]);

        $containerBuilder->useAutowiring(true);

        $this->diContainer = $containerBuilder->build();

        $adapterHelper = new AdapterDILoader();
        $adapterHelper->addDefinitionsToDi($this->diContainer, $config);

    }

    public function getDIContainer(): ContainerInterface
    {
        return $this->diContainer;
    }

    public function run()
    {
        /** @var Client $attlazClient */
        $attlazClient = $this->diContainer->get(Client::class);
        $environment = $this->environment;
        if (PHP_SAPI === 'cli') {
            /** @var Config $config */
            $config = $this->diContainer->get(Config::class);

            $commandManager = $this->commandManager;

            $cliApplication = new Application();
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
