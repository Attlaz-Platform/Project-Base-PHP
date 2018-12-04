<?php

namespace Attlaz\Project\App;

use Attlaz\Project\Cache\CacheManager;
use Echron\Tools\FileSystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class Config
{
    private $cacheManager;
    private $environment;
    private $logger;

    private $configuration = [];

    public function __construct(CacheManager $cacheManager, Environment $environment, LoggerInterface $logger)
    {
        $this->cacheManager = $cacheManager;
        $this->environment = $environment;
        $this->logger = $logger;
    }

    public function loadConfig(): void
    {
        $this->configuration = $this->parseConfig();
    }

    private function parseConfig(): array
    {
        //For speed this should only be saved to local cache?
        $cache = $this->cacheManager->getCache('config');

        if ($this->environment->cacheConfig && $cache->has('config')) {
            return $cache->get('config');
        }
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
                    $this->logger->warning('Ignore local config value "' . $key . '": not allowed to override');
                }
            } else {
                $result[$key] = $localConfigValue;
            }
        }
        if ($this->environment->cacheConfig) {
            $cache->set('config', $result);
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

    private function fetchLocalConfigValues(): array
    {
        $configFilePath = $this->environment->getConfigFilePath();
        $result = [];
        if (!FileSystem::fileExists($configFilePath)) {
            $this->logger->debug('No local configuration defined');
        } else {
            try {
                $values = Yaml::parseFile($configFilePath);
                if (!\is_array($values)) {
                    $this->logger->debug('No valid local configuration');
                    // TODO: throw an exception or just ignore this?
                    //throw new \Exception('Invalid config file: No values');
                } else {
                    $configValues = $this->flatten($values);

                    foreach ($configValues as $key => $value) {
                        $result[$key] = [
                            'value'  => $value,
                            'source' => 'local',
                        ];
                    }
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