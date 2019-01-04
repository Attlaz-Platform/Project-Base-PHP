<?php
declare(strict_types=1);

namespace Attlaz\Project\App;

use Echron\Tools\FileSystem;
use Psr\Log\LoggerInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigHelper
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function fetchLocalConfigValues(string $configFilePath): array
    {
        $result = [];
        if (!FileSystem::fileExists($configFilePath)) {
            //            if ($this->logger) {
            $this->logger->debug('No local configuration defined');
            //            }
        } else {
            try {
                $values = Yaml::parseFile($configFilePath);
                if (!\is_array($values)) {
                    //                    if ($this->logger) {
                    $this->logger->debug('No valid local configuration');
                    //                    }
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
            } catch (ParseException $ex) {
                throw new \Exception('Invalid config file: ' . $ex->getMessage());
            }
        }

        return $result;
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

    public function patchConfigVariables(array $config, array $variables): array
    {
        foreach ($config as $key => $configValue) {
            $value = $configValue['value'];
            if (\is_string($value)) {
                //TODO: use regex to replace config variables

                foreach ($variables as $variableKey => $variableValue) {
                    $value = \str_replace('{{' . $variableKey . '}}', $variableValue, $value);
                }
            }

            $config[$key]['value'] = $value;
        }

        return $config;
}
}
