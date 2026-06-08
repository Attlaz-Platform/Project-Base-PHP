<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Client;
use Attlaz\Helper\LoadAllHelper;
use Attlaz\Model\CursorPagination;
use Attlaz\Model\ProjectEnvironment;
use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use Echron\Tools\StringHelper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigList extends Command
{
    //    private const ARG_FORCE_ENVIRONMENT = 'force-config-environment';

    public function __construct(
        protected readonly Config          $config,
        protected readonly Environment     $environment,
        protected readonly Client          $attlazClient,
        protected readonly LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('config:list')
            ->setDescription('List configuration')
            ->setHelp('List configuration');
        // ->addOption(self::ARG_FORCE_ENVIRONMENT, null, InputOption::VALUE_OPTIONAL, 'Force the configuration to be fetched from this environment (id or key)', null);
    }

//    private function getForcedConfigProjectEnvironment(InputInterface $input): ?ProjectEnvironment
//    {
//        $value = $input->getOption(self::ARG_FORCE_ENVIRONMENT);
//
//        if (!\is_null($value)) {
//            $identifier = trim($value);
//
//            if (\is_numeric($identifier)) {
//                $id = (int)$identifier . '';
//
//                return $this->attlazClient->getProjectEnvironmentEndpoint()->getProjectEnvironmentById($id);
//            } else {
//                return $this->attlazClient->getProjectEnvironmentEndpoint()->getProjectEnvironmentByKey($this->environment->getProject()->id, $identifier);
//            }
//        }
//
//        return null;
//    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $environments = LoadAllHelper::loadAll(fn(CursorPagination $pagination) => $this->attlazClient->getProjectEnvironmentEndpoint()->getProjectEnvironments($this->environment->getProject()->id, $pagination));

            $totalConfigValues = [];
            foreach ($environments as $environment) {
                $configValues = $this->config->getConfigValues($environment);
                foreach ($configValues as $configValue) {
                    if (!isset($totalConfigValues[$configValue->key])) {
                        $totalConfigValues[$configValue->key] = [];
                    }

                    $totalConfigValues[$configValue->key][$environment->key] = $configValue;
                }
            }

            $rows = [];
            foreach ($totalConfigValues as $key => $configValues) {
                $rows[] = $this->formatConfigValues($key, $configValues, $environments);
            }
            $table = new Table($output);
            $table->setStyle('box');

            $headers = ['Key'];
            foreach ($environments as $environment) {
                $headers[] = $environment->name;
            }

            $table->setHeaders($headers)
                ->setRows($rows);
            $table->render();

            return 0;
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }

    /**
     * @param \Attlaz\Project\Model\Config[] $configValues
     * @param ProjectEnvironment[] $environments
     * @return string[]
     */
    private function formatConfigValues(string $key, array $configValues, array $environments): array
    {
        $result = [$key];

        foreach ($environments as $environment) {
            $value = '';
            if (isset($configValues[$environment->key])) {
                $config = $configValues[$environment->key];
                $value = $this->formatConfigValue($config);
            }
            $result[] = $value;
        }

        return $result;
    }

    private function formatConfigValue(\Attlaz\Project\Model\Config $config): string
    {
        $value = $config->value;
        if ($config->sensitive) {
            $value = StringHelper::mask($value, '*', 2, 2);
        }
        $value = $value . ' (' . \gettype($value) . ')';

        return $value;
    }
}
