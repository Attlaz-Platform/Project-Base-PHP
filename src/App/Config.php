<?php
declare(strict_types=1);

namespace Attlaz\Project\App;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Echron\Tools\Normalize\Normalizer;

class Config
{

    public const MODE_PRODUCTION = 'production';
    public const MODE_DEVELOPMENT = 'development';

    public $branch;
    public $mode;
    public $definitionsFile;

    public $api_endpoint;
    public $api_client_id;
    public $api_client_secret;

    public $mongoDBConnectionString;

    private $projectRootPath;

    public const SOURCE_LOCATION = \DIRECTORY_SEPARATOR . 'src';
    private const DI_FILE_LOCATION = '/App/etc/di.php';
    private const COMMANDS_LOCATION = '/App/Command';

    public $compileDi = false;
    public $logVerbose = true;

    public function __construct(string $projectRootPath)
    {
        //TODO: validate root path;
        $this->projectRootPath = $projectRootPath;

        //TODO: handle that env file is not readable
        try {
            $dotenv = new Dotenv($projectRootPath);
            $dotenv->load();
        } catch (InvalidPathException $ex) {
            throw new \Exception('Unable to start project: .env file missing in directory "' . $projectRootPath . '"');
        }

        $this->branch = $this->getEnvValue('project');

        $this->api_endpoint = $this->getEnvValue('api_endpoint');
        $this->api_client_id = $this->getEnvValue('api_client_id');
        $this->api_client_secret = $this->getEnvValue('api_client_secret');

        $this->mongoDBConnectionString = $this->getEnvValue('storage');

        $diFile = $this->getDIFileLocation($projectRootPath);

        if (\file_exists($diFile)) {
            $this->definitionsFile = $diFile;
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

    private function getDIFileLocation(string $projectRootPath): string
    {
        return realpath($projectRootPath . '/' . self::SOURCE_LOCATION . '/' . self::DI_FILE_LOCATION);
    }

    public static function getCommandDirectoryPath(string $projectRootPath): string
    {
        return realpath($projectRootPath . '/' . self::SOURCE_LOCATION . '/' . self::COMMANDS_LOCATION);
    }
}
