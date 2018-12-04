<?php
declare(strict_types=1);

namespace Attlaz\Project\App;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Echron\Tools\Normalize\Normalizer;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

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
    private const CONFIG_FILE_LOCATION = '/App/etc/config.yaml';
    private const COMMANDS_LOCATION = '/App/Command';

    public $compileDi = false;
    public $logVerbose = true;

    private $configuration = [];

    public function __construct(string $projectRootPath)
    {
        $this->projectRootPath = realpath($projectRootPath);

        //TODO: handle that env file is not readable
        try {
            $dotenv = new Dotenv($this->projectRootPath);
            $dotenv->load();
        } catch (InvalidPathException $ex) {
            throw new \Exception('Unable to start project: .env file missing in directory "' . $this->projectRootPath . '"');
        }

        $this->branch = $this->getEnvValue('project');

        $this->api_endpoint = $this->getEnvValue('api_endpoint');
        $this->api_client_id = $this->getEnvValue('api_client_id');
        $this->api_client_secret = $this->getEnvValue('api_client_secret');

        $this->mongoDBConnectionString = $this->getEnvValue('storage');

        $diFile = $this->getDIFileLocation($projectRootPath);

        if (!is_null($diFile) && \file_exists($diFile)) {
            $this->definitionsFile = $diFile;
        }

        $this->configuration = $this->parseConfig();
    }

    private function parseConfig(): array
    {
        $result = [];
        //TODO: read from cache if possible
        //TODO: when to flush cache (new build?)

        //TODO: read database/API config values
        $apiConfigValues = $this->fetchApiConfigValues();
        $result = $apiConfigValues;

        //TODO: what can override the rest?
        $localConfigValues = $this->fetchLocalConfigValues();

        foreach ($localConfigValues as $key => $localConfigValue) {
            if (isset($apiConfigValues[$key])) {
                if ($apiConfigValues[$key]['allowoverride']) {
                    $result[$key] = $localConfigValue;
                } else {
                    echo 'Ignore local config value "' . $key . '": not allowed to override' . \PHP_EOL;
                }
            } else {
                $result[$key] = $localConfigValue;
            }
        }

        return $result;
    }

    private function fetchApiConfigValues(): array
    {
        $result = [];

        $result['testkey'] = [
            'value'         => 'testvalue',
            'allowoverride' => false,
            'source'        => 'api',

        ];

        return $result;
    }

    private function fetchLocalConfigValues()
    {
        $configFilePath = $this->getConfigFilePath();
        if (\is_null($configFilePath)) {
            throw new \Exception('Config file not found');
        } else {
            try {
                $values = Yaml::parseFile($configFilePath);

                $configValues = $this->flatten($values);

                $result = [];

                foreach ($configValues as $key => $value) {
                    $result[$key] = [
                        'value'  => $value,
                        'source' => 'local',
                    ];
                }

                return $result;
            } catch (ParseException $ex) {
                throw new \Exception('Invalid config file: ' . $ex->getMessage());
            }
        }
    }

    private function flatten(array $values): array
    {
        //TODO: this can better!
        $result = [];
        foreach ($values as $key => $value) {
            if (\is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (\is_array($subValue)) {
                        foreach ($subValue as $subSubKey => $subSubValue) {
                            $result[$key . '_' . $subKey . '_' . $subSubKey] = $subSubValue;
                            //TODO: make recursive
                        }
                    } else {
                        $result[$key . '_' . $subKey] = $subValue;
                    }
                }
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
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

    private function getConfigFilePath(): ?string
    {
        $configFilePath = realpath($this->projectRootPath . '/' . self::SOURCE_LOCATION . '/' . self::CONFIG_FILE_LOCATION);
        if ($configFilePath === false) {
            return null;
        }

        return $configFilePath;
    }

    public static function getCommandDirectoryPath(string $projectRootPath): ?string
    {
        $commandDirectoryPath = realpath($projectRootPath . '/' . self::SOURCE_LOCATION . '/' . self::COMMANDS_LOCATION);
        if ($commandDirectoryPath === false) {
            return null;
        }

        return $commandDirectoryPath;
    }

    public function has(string $key): bool
    {
        return isset($this->configuration[$key]);
    }

    public function get(string $key)
    {
        if (isset($this->configuration[$key])) {
            return $this->configuration[$key]['value'];
        }

        return null;
    }

    public function getConfigValues(): array
    {
        return $this->configuration;
    }
}
