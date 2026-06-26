<?php

declare(strict_types=1);

namespace Attlaz\Project\App;

use Attlaz\Model\Project as ProjectModel;
use Attlaz\Model\ProjectEnvironment;
use Attlaz\Project\DI\InternalFactory;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Echron\Tools\FileSystem;
use Echron\Tools\Normalize\Normalizer;
use Monolog\Level;

class Environment
{
    public const string MODE_PRODUCTION = 'production';
    public const string MODE_DEVELOPMENT = 'development';
    public const string SOURCE_LOCATION = \DIRECTORY_SEPARATOR . 'src';
    private const string CACHE_LOCATION = 'var/cache';
    private const string DI_FILE_LOCATION = '/App/etc/di.php';
    private const string CONFIG_FILE_LOCATION = '/App/etc/config.yaml';
    public const string COMMANDS_LOCATION = '/App/Command';
    public const string ENV_PROJECT_ENVIRONMENT = 'project_environment';
    public const string ENV_MODE = 'mode';
    public const string ENV_API_ENDPOINT = 'api_endpoint';
    public const string ENV_API_CLIENT_ID = 'api_client_id';
    public const string ENV_API_CLIENT_SECRET = 'api_client_secret';
    public const string ENV_API_TOKEN = 'api_token';
    public const string ENV_SYS_MEMORY_LIMIT = 'sys_memory_limit';
    /**
     * Config values
     */
    public bool $compileDi = false;
    public bool $cacheConfig = false;
    public string|null $definitionsFile = null;
    public string $api_endpoint = 'https://api.attlaz.com/beta';
    public string|null $api_client_id = null;
    public string|null $api_client_secret = null;
    public string|null $api_client_token = null;
    public string $sys_memory_limit = '2G';
    public string $sys_timezone = 'Europe/Brussels';
    public bool $cli_log_verbose = true;
    public Level $cli_log_level = Level::Debug;
    public bool $cli_log_stacktrace = true;
    public Level $api_log_level_flow_run = Level::Info;
    private string $projectRootPath;
    private ProjectModel|null $project = null;
    private ProjectEnvironment|null $projectEnvironment = null;
    private bool $isInitialized = false;

    public function __construct(string $projectRootPath)
    {
        $this->projectRootPath = realpath($projectRootPath) . \DIRECTORY_SEPARATOR;

        ini_set('memory_limit', $this->sys_memory_limit);
        date_default_timezone_set($this->sys_timezone);

        //if ($this->mode === Environment::MODE_DEVELOPMENT) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        //}

        $this->loadEnvSettingsFromFile();
        $this->checkIfInitialized();

        if ($this->isInitialized) {
            $this->api_endpoint = $this->getEnvValue(self::ENV_API_ENDPOINT, $this->api_endpoint);

            $this->api_client_token = $this->getEnvValue(self::ENV_API_TOKEN, null);


            if ($this->api_client_token === null) {
                $this->api_client_id = $this->getEnvValue(self::ENV_API_CLIENT_ID, null);
                $this->api_client_secret = $this->getEnvValue(self::ENV_API_CLIENT_SECRET, null);
            }


            $this->sys_memory_limit = $this->getEnvValue(self::ENV_SYS_MEMORY_LIMIT, $this->sys_memory_limit);
        }
        //TODO: check if we were able to set this
        ini_set('memory_limit', $this->sys_memory_limit);
        date_default_timezone_set($this->sys_timezone);

        $diFile = $this->getDIFileLocation($projectRootPath);

        if ($diFile !== null && \file_exists($diFile)) {
            $this->definitionsFile = $diFile;
        }
    }

    public static function getCommandDirectoryPath(string $projectRootPath): string
    {
        $commandDirectoryPath = FileSystem::joinPath($projectRootPath, self::SOURCE_LOCATION, self::COMMANDS_LOCATION);
        return realpath($commandDirectoryPath);
    }

    public function getEnvFilePath(): string
    {
        return $this->projectRootPath . \DIRECTORY_SEPARATOR . '.env';
    }

    public function getCacheName(): string
    {
        //        if (!$this->isInitialized()) {
        //            return \strtolower(Normalizer::normalize('x'));
        //        }

        return \strtolower(Normalizer::normalize($this->getProject()->key . '_' . $this->getProjectEnvironment()->key));
    }

    public function getProjectRootPath(): string
    {
        return $this->projectRootPath;
    }

//    private function getNumEnvValue(string $key): int
//    {
//        $value = $this->getEnvValue($key);
//
//        return (int)$value;
//    }

    public function getConfigFilePath(): string
    {
        return FileSystem::joinPath($this->projectRootPath, self::SOURCE_LOCATION, self::CONFIG_FILE_LOCATION);
    }

    public function getFileCachePath(): string
    {
        $cachePath = FileSystem::joinPath($this->projectRootPath, self::CACHE_LOCATION);
        if (!FileSystem::dirExists($cachePath)) {
            FileSystem::createDir($cachePath, true);
            if (!FileSystem::dirExists($cachePath)) {
                throw new \Exception('Unable to create cache directory');
            }
        }
        return $cachePath;
    }

    public function getProject(): ProjectModel
    {
        return $this->project;
    }

    public function getProjectEnvironment(): ProjectEnvironment
    {
        return $this->projectEnvironment;
    }

    public function init(): void
    {
        if ($this->isInitialized) {
            $client = InternalFactory::getClient($this);
            $client->setDebug(1);

            $projectEnvironmentId = $this->getEnvValue(self::ENV_PROJECT_ENVIRONMENT);

            $this->projectEnvironment = $client->getProjectEnvironmentEndpoint()->getProjectEnvironmentById($projectEnvironmentId);
            $this->project = $client->getProjectEndpoint()->getProjectById($this->projectEnvironment->projectId);
        }
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    public function getDashboardUrl(): string
    {
        return 'https://app.attlaz.com';
    }

    /**
     * @param ProjectEnvironment|null $environment
     * @param string[] $segments
     * @return string
     */
    public function getAppUrl(ProjectEnvironment|null $environment = null, array $segments = []): string
    {


        $urlSegments = [
            'https://app.attlaz.com',
        ];
        if ($this->isInitialized) {
            $urlSegments[] = $this->getProject()->workspaceId;
            $urlSegments[] = $this->getProject()->key;

            if (\is_null($environment)) {
                $environment = $this->getProjectEnvironment();
            }
            $urlSegments[] = $environment->key;
        }
        $urlSegments = \array_merge($urlSegments, $segments);

        return \implode('/', $urlSegments);
    }

    private function checkIfInitialized(): void
    {
        $requiredEnvValues = [
            self::ENV_PROJECT_ENVIRONMENT,
            //  self::ENV_API_ENDPOINT,
//            self::ENV_API_CLIENT_ID,
//            self::ENV_API_CLIENT_SECRET,
        ];
        foreach ($requiredEnvValues as $requiredEnvValue) {
            $value = $this->getEnvValue($requiredEnvValue, '');
            if ($value === '') {
                $this->isInitialized = false;

                return;
            }
        }
        $this->isInitialized = true;
    }

    private function loadEnvSettingsFromFile(): void
    {
        try {

            $dotenv = Dotenv::createMutable($this->projectRootPath, '.env');
            $dotenv->load();

            $this->isInitialized = true;
        } catch (InvalidPathException $ex) {

            $this->isInitialized = false;
        }
    }

    private function getEnvValue(string $key, string|null $fallback = null): string|null
    {
        $value = null;

        if (\array_key_exists($key, $_SERVER)) {
            $value = $_SERVER[$key];
        }

        if ($value === null) {
            return $fallback;
        }

        if (!\is_string($value)) {
            $value = (string)$value;
        }

        return $value;
    }

    private function getDIFileLocation(string $projectRootPath): string|null
    {
        $diFileLocation = FileSystem::joinPath($projectRootPath, self::SOURCE_LOCATION, self::DI_FILE_LOCATION);
        $x = realpath($diFileLocation);
        if ($x === false) {
            return null;
        }
        return $x;
    }
}
