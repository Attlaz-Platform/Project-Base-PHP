<?php

declare(strict_types=1);

namespace Attlaz\Project\App;

use Attlaz\Model\Project as ProjectModel;
use Attlaz\Model\ProjectEnvironment;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Echron\Tools\FileSystem;
use Echron\Tools\Normalize\Normalizer;

class Environment
{

    public const MODE_PRODUCTION = 'production';
    public const MODE_DEVELOPMENT = 'development';

    private $projectRootPath;

    public const SOURCE_LOCATION = \DIRECTORY_SEPARATOR . 'src';
    private const CACHE_LOCATION = 'var/cache';
    private const DI_FILE_LOCATION = '/App/etc/di.php';
    private const CONFIG_FILE_LOCATION = '/App/etc/config.yaml';
    public const COMMANDS_LOCATION = '/App/Command';

    /**
     * Config values
     */
    public $compileDi = false;
    public $cacheConfig = false;

    private $project;
    private $projectEnvironment;

    public $mode;
    public $definitionsFile;

    public $api_endpoint = 'https://api.attlaz.com';
    public $api_client_id = 'public_client_id';
    public $api_client_secret = 'public_client_secret';

    public $sys_memory_limit = '2G';
    public $sys_timezone = 'Europe/Brussels';

    public $cli_log_verbose = true;
    public $cli_log_level = \Monolog\Logger::INFO;
    public $cli_log_stacktrace = true;

    public $mongoDBConnectionString;

    private $isInitialized = false;

    public const ENV_PROJECT = 'project';
    public const ENV_PROJECT_ENVIRONMENT = 'project_environment';
    public const ENV_MODE = 'mode';
    public const ENV_API_ENDPOINT = 'api_endpoint';
    public const ENV_API_CLIENT_ID = 'api_client_id';
    public const ENV_API_CLIENT_SECRET = 'api_client_secret';
    public const ENV_STORAGE = 'storage';

    public const ENV_SYS_MEMORY_LIMIT = 'sys_memory_limit';

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
            $this->api_endpoint = $this->getEnvValue(self::ENV_API_ENDPOINT);
            $this->api_client_id = $this->getEnvValue(self::ENV_API_CLIENT_ID);
            $this->api_client_secret = $this->getEnvValue(self::ENV_API_CLIENT_SECRET);

            $this->sys_memory_limit = $this->getEnvValue(self::ENV_SYS_MEMORY_LIMIT, $this->sys_memory_limit);

            $this->mongoDBConnectionString = $this->getEnvValue(self::ENV_STORAGE);
        }
        //TODO: check if we were able to set this
        ini_set('memory_limit', $this->sys_memory_limit);
        date_default_timezone_set($this->sys_timezone);

        $diFile = $this->getDIFileLocation($projectRootPath);

        if (!is_null($diFile) && \file_exists($diFile)) {
            $this->definitionsFile = $diFile;
        }
    }

    private function checkIfInitialized(): void
    {
        $requiredEnvValues = [
            self::ENV_API_ENDPOINT,
            self::ENV_API_CLIENT_ID,
            self::ENV_API_CLIENT_SECRET,
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
            $dotenv = new Dotenv($this->projectRootPath);
            $dotenv->load();

            $this->isInitialized = true;
        } catch (InvalidPathException $ex) {
            $this->isInitialized = false;
        }
    }

    public function getEnvFilePath(): string
    {
        return $this->projectRootPath . \DIRECTORY_SEPARATOR . '.env';
    }

    private function getEnvValue(string $key, string $failback = null): string
    {
        $value = \getenv($key);
        if ($value === false) {
            if (\is_null($failback)) {
                throw new \Exception('Environment variable "' . $key . '" not defined');
            } else {
                $value = $failback;
            }
        }

        if (!\is_string($value)) {
            $value = strval($value);
        }

        return $value;
    }

    private function getNumEnvValue(string $key): int
    {
        $value = $this->getEnvValue($key);

        return \intval($value);
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

    private function getDIFileLocation(string $projectRootPath): ?string
    {
        $diFileLocation = FileSystem::joinPath($projectRootPath, self::SOURCE_LOCATION, self::DI_FILE_LOCATION);
        $diFileLocation = realpath($diFileLocation);
        if ($diFileLocation === false) {
            return null;
        }

        return $diFileLocation;
    }

    public function getConfigFilePath(): string
    {
        return FileSystem::joinPath($this->projectRootPath, self::SOURCE_LOCATION, self::CONFIG_FILE_LOCATION);
    }

    public static function getCommandDirectoryPath(string $projectRootPath): ?string
    {
        $commandDirectoryPath = FileSystem::joinPath($projectRootPath, self::SOURCE_LOCATION, self::COMMANDS_LOCATION);
        $commandDirectoryPath = realpath($commandDirectoryPath);
        if ($commandDirectoryPath === false) {
            return null;
        }

        return $commandDirectoryPath;
    }

    public function getFileCachePath(): string
    {
        $cachePath = FileSystem::joinPath($this->projectRootPath, self::CACHE_LOCATION);
        FileSystem::createDir($cachePath, true);
        if (!FileSystem::dirExists($cachePath)) {
            throw new \Exception('Unable to create cache directory');
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
            //TODO: load this from DI
            $client = new \Attlaz\Client($this->api_endpoint, $this->api_client_id, $this->api_client_secret);

            $projectId = $this->getEnvValue(self::ENV_PROJECT);
            $this->project = $client->getProjectById($projectId);

            $projectEnvironmentId = $this->getNumEnvValue(self::ENV_PROJECT_ENVIRONMENT);
            $this->projectEnvironment = $client->getProjectEnvironmentById($projectEnvironmentId);
        }
    }

    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    public function getAppUrl(ProjectEnvironment $environment = null, array $segments = []): string
    {
        $teamKey = $this->getProject()->team;
        $projectKey = $this->getProject()->key;

        if (\is_null($environment)) {
            $environment = $this->getProjectEnvironment();
        }
        $environmentKey = $environment->key;

        $urlSegments = [
            'https://app.attlaz.com',
            $teamKey,
            $projectKey,
            $environmentKey,

        ];

        $urlSegments = \array_merge($urlSegments, $segments);

        return \implode('/', $urlSegments);
    }
}
