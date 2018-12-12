<?php
declare(strict_types=1);

namespace Attlaz\Project\App;

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
    private const COMMANDS_LOCATION = '/App/Command';

    /**
     * Config values
     */
    public $compileDi = false;
    public $cacheConfig = false;

    public $branch;
    public $mode;
    public $definitionsFile;

    public $api_endpoint;
    public $api_client_id;
    public $api_client_secret;

    public $sys_memory_limit = '2G';
    public $sys_timezone = 'Europe/Brussels';

    public $cli_log_verbose = true;
    public $cli_log_level = \Monolog\Logger::NOTICE;
    public $cli_log_stacktrace = false;

    public $mongoDBConnectionString;

    public function __construct(string $projectRootPath)
    {
        $this->projectRootPath = realpath($projectRootPath);

        ini_set('memory_limit', $this->sys_memory_limit);
        date_default_timezone_set($this->sys_timezone);

        //if ($this->mode === Environment::MODE_DEVELOPMENT) {
        error_reporting(E_ALL);
        ini_set('display_errors', '1');
        //}

        $this->loadSettings();

        $this->branch = $this->getEnvValue('project');

        $this->api_endpoint = $this->getEnvValue('api_endpoint');
        $this->api_client_id = $this->getEnvValue('api_client_id');
        $this->api_client_secret = $this->getEnvValue('api_client_secret');

        $this->mongoDBConnectionString = $this->getEnvValue('storage');

        $diFile = $this->getDIFileLocation($projectRootPath);

        if (!is_null($diFile) && \file_exists($diFile)) {
            $this->definitionsFile = $diFile;
        }
    }

    private function loadSettings(): void
    {
        //TODO: handle that env file is not readable
        try {
            $dotenv = new Dotenv($this->projectRootPath);
            $dotenv->load();
        } catch (InvalidPathException $ex) {
            throw new \Exception('Unable to start project: .env file missing in directory "' . $this->projectRootPath . '"');
        }
    }

    private function getEnvValue(string $key)
    {
        $value = \getenv($key);
        if ($value === false) {
            throw new \Exception('Config variable "' . $key . '" not defined');
        }

        if (!\is_string($value)) {
            $value = strval($value);
        }

        return $value;
    }

    public function getCacheName(): string
    {
        return \strtolower(Normalizer::normalize($this->branch));
    }

    public function getProjectRootPath(): string
    {
        return $this->projectRootPath;
    }

    private function getDIFileLocation(string $projectRootPath): ?string
    {
        $diFileLocation = realpath($projectRootPath . '/' . self::SOURCE_LOCATION . '/' . self::DI_FILE_LOCATION);
        if ($diFileLocation === false) {
            return null;
        }

        return $diFileLocation;
    }

    public function getConfigFilePath(): string
    {
        return $this->projectRootPath . '/' . self::SOURCE_LOCATION . '/' . self::CONFIG_FILE_LOCATION;
    }

    public static function getCommandDirectoryPath(string $projectRootPath): ?string
    {
        $commandDirectoryPath = realpath($projectRootPath . '/' . self::SOURCE_LOCATION . '/' . self::COMMANDS_LOCATION);
        if ($commandDirectoryPath === false) {
            return null;
        }

        return $commandDirectoryPath;
    }

    public function getFileCachePath(): string
    {
        $cachePath = $this->projectRootPath . '/' . self::CACHE_LOCATION;
        FileSystem::createDir($cachePath, true);
        if (!FileSystem::dirExists($cachePath)) {
            throw new \Exception('Unable to create cache directory');
        }

        return $cachePath;
    }
}
