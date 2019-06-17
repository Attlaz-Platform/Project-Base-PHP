<?php
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Project\App\Config;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigList extends Command
{
    protected $config;
    protected $logger;

    public function __construct(Config $config, LoggerInterface $logger)
    {
        parent::__construct();

        $this->config = $config;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('config:list')
             ->setDescription('List configuration')
             ->setHelp('List configuration');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $configValues = $this->config->getConfigValues();

            $rows = [];
            foreach ($configValues as $key => $value) {
                $rows[] = $this->formatConfigValue($key, $value);
            }
            $table = new Table($output);
            $table->setStyle('box');
            $table->setHeaders([
                'Key',
                'Value',
                'Source',
            ])
                  ->setRows($rows);
            $table->render();

            return 0;
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }

    private function formatConfigValue(string $key, array $configValue): array
    {
        $source = $configValue['source'];
        $value = $configValue['value'];
        $value = $configValue['value'] . ' (' . \gettype($value) . ')';

        return [
            $key,
            $value,
            $source,
        ];
    }
}
