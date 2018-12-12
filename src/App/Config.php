<?php

namespace Attlaz\Project\App;

use Attlaz\Project\Cache\CacheManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Config implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $cacheManager;
    private $environment;
    private $configHelper;

    private $configuration = [];

    private const CONFIG_CACHE_POOL = 'config';
    private const CONFIG_CACHE_KEY = 'config';

    public function __construct(CacheManager $cacheManager, Environment $environment, ConfigHelper $configHelper)
    {
        $this->cacheManager = $cacheManager;
        $this->environment = $environment;
        $this->configHelper = $configHelper;
    }

    public function loadConfig(): void
    {
        $this->configuration = $this->parseConfig();
    }

    private function parseConfig(): array
    {
        $cache = $this->cacheManager->getCache(self::CONFIG_CACHE_POOL);

        if ($this->environment->cacheConfig && $cache->has(self::CONFIG_CACHE_KEY)) {
            return $cache->get(self::CONFIG_CACHE_KEY);
        }
        $result = [];
        //TODO: read from cache if possible
        //TODO: when to flush cache (new build?)

        //TODO: read database/API config values
        $apiConfigValues = $this->fetchApiConfigValues();
        $result = $apiConfigValues;

        //TODO: what can override the rest?
        $configFilePath = $this->environment->getConfigFilePath();
        $localConfigValues = $this->configHelper->fetchLocalConfigValues($configFilePath);

        foreach ($localConfigValues as $key => $localConfigValue) {
            if (isset($apiConfigValues[$key])) {
                if ($apiConfigValues[$key]['allowoverride']) {
                    $result[$key] = $localConfigValue;
                } else {
                    if ($this->logger) {
                        $this->logger->warning('Ignore local config value "' . $key . '": not allowed to override');
                    }
                }
            } else {
                $result[$key] = $localConfigValue;
            }
        }
        if ($this->environment->cacheConfig) {
            $cache->set(self::CONFIG_CACHE_KEY, $result);
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

    public function has(string $key): bool
    {
        return isset($this->configuration[$key]);
    }

    public function get(string $key)
    {
        if (isset($this->configuration[$key])) {
            return $this->configuration[$key]['value'];
        }

        throw new \Exception('Unable to resolve config value for "' . $key . '"');
    }

    public function getConfigValues(): array
    {
        return $this->configuration;
    }
}