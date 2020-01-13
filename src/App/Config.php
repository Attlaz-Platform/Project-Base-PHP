<?php

declare(strict_types=1);

namespace Attlaz\Project\App;

use Attlaz\Client;
use Attlaz\Model\ProjectEnvironment;
use Attlaz\Project\Cache\CacheManager;
use Attlaz\Project\Model\Config as ProjectConfig;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Config implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private $cacheManager;
    private $client;
    private $environment;
    private $configHelper;

    private $configuration = [];

    private const CONFIG_CACHE_POOL = 'config';
    private const CONFIG_CACHE_PREFIX_KEY = 'config_';

    public function __construct(
        CacheManager $cacheManager,
        Client $client,
        Environment $environment,
        ConfigHelper $configHelper
    ) {
        $this->cacheManager = $cacheManager;
        $this->client = $client;
        $this->environment = $environment;
        $this->configHelper = $configHelper;
    }

    public function loadConfig(ProjectEnvironment $projectEnvironment = null): void
    {
        if ($this->environment->isInitialized()) {
            if (\is_null($projectEnvironment)) {
                $projectEnvironment = $this->environment->getProjectEnvironment();
            }

            $this->configuration = $this->parseConfig($projectEnvironment);
        }
    }

    private function parseConfig(ProjectEnvironment $projectEnvironment): array
    {
        $configCacheKey = self::CONFIG_CACHE_PREFIX_KEY . $projectEnvironment->id;

        $cache = $this->cacheManager->getCache(self::CONFIG_CACHE_POOL);

        if ($this->environment->cacheConfig && $cache->has($configCacheKey)) {
            return $cache->get($configCacheKey);
        }
        $result = [];
        //TODO: read from cache if possible
        //TODO: when to flush cache (new build?)

        //TODO: read database/API config values
        $apiConfigValues = $this->fetchApiConfigValues($this->environment->getProject(), $projectEnvironment);

        foreach ($apiConfigValues as $apiConfigValue) {
            $result[$apiConfigValue->key] = $apiConfigValue;
        }

        //TODO: what can override the rest?

        if ($projectEnvironment->id === $this->environment->getProjectEnvironment()->id) {
            $configFilePath = $this->environment->getConfigFilePath();
            $localConfigValues = $this->configHelper->fetchLocalConfigValues($configFilePath);

            foreach ($localConfigValues as $localConfigValue) {
                $key = $localConfigValue->key;
                /** @var ProjectConfig|null $apiConfigValue */
                $apiConfigValue = current(array_filter($apiConfigValues, function (
                    ProjectConfig $apiConfigValue
                ) use (
                    $key
                ) {
                    return $apiConfigValue->key === $key;
                }));

                //                if (!\is_null($apiConfigValue)) {
                //                    if ($apiConfigValue->inheritable || true) {
                //                        $result[$key] = $localConfigValue;
                //                    } else {
                //                        if (\is_object($this->logger)) {
                //$this->logger->warning('Ignore local config value "' . $key . '": not allowed to override');
                //                        }
                //                    }
                //                } else {
                $result[$key] = $localConfigValue;
                //                }
            }
        }
        $configVariables = [
            'project_dir' => $this->environment->getProjectRootPath(),
        ];
        $result = $this->configHelper->patchConfigVariables($result, $configVariables);
        if ($this->environment->cacheConfig) {
            $cache->set($configCacheKey, $result);
        }

        return $result;
    }

    /**
     * @param string $projectId
     * @param int|null $projectEnvironmentId
     * @return ProjectConfig[]
     * @throws \Attlaz\Model\Exception\RequestException
     */
    private function fetchApiConfigValues(
        \Attlaz\Model\Project $project,
        ProjectEnvironment $projectEnvironment = null
    ): array {
        $projectEnvironmentId = null;
        if (!\is_null($projectEnvironment)) {
            $projectEnvironmentId = $projectEnvironment->id;
        }
        $configValues = $this->client->getConfigByProject($project->id, $projectEnvironmentId);

        $result = [];

        foreach ($configValues as $configValue) {
            $configValue = ProjectConfig::fromBase($configValue);
            $configValue->source = 'api (environment ' . $configValue->projectEnvironment . ')';
            $result[] = $configValue;
        }

        return $result;
    }

    public function has(string $key): bool
    {
        return isset($this->configuration[$key]);
    }

    public function get(string $key, string $datatype = null)
    {
        $configValue = $this->getConfig($key);
        if (\is_null($configValue)) {
            throw new \Exception('Unable to resolve config value for "' . $key . '"');
        }
        $value = $configValue->value;
        if (!\is_null($datatype)) {
            switch ($datatype) {
                case 'string':
                    break;
                case 'int':
                case 'integer':
                    $value = \intval($value);
                    break;
                default:
                    throw new \Exception('Unable to cast config value to "' . $datatype . '": unknown type');
            }
        }

        return $value;
    }

    public function getConfig(string $key): ?ProjectConfig
    {
        if (isset($this->configuration[$key])) {
            return $this->configuration[$key];
        }

        return null;
    }

    /**
     * @return ProjectConfig[]
     */
    public function getConfigValues(ProjectEnvironment $projectEnvironment = null): array
    {
        if (\is_null($projectEnvironment)) {
            $projectEnvironment = $this->environment->getProjectEnvironment();
        }
        $result = $this->parseConfig($projectEnvironment);

        return \array_values($result);
    }

    //    private function formatProjectEnvironmentIdentifier($forceEnvironmentId = null): ProjectEnvironment
    //    {
    //        if (\is_numeric($forceEnvironmentId)) {
    //            return $this->client->getProjectEnvironmentById($forceEnvironmentId);
    //        } else {
    //            if ($forceEnvironmentId instanceof string) {
    //                return $this->client->getProjectEnvironmentByKey($forceEnvironmentId);
    //            }
    //        }
    //
    //        return $this->environment->getProjectEnvironment();
    //    }
}
