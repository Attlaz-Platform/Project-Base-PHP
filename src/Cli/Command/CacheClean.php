<?php
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Project\App\Config;
use Attlaz\Project\Cache\CacheManager;
use Attlaz\Project\Logger\Logger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClean extends Command
{
    protected $config;
    protected $cacheManager;
    protected $logger;

    public function __construct(Config $config, CacheManager $cacheManager, Logger $logger)
    {
        parent::__construct();

        $this->config = $config;
        $this->cacheManager = $cacheManager;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('cache:clean')
             ->setDescription('Clean cache')
             ->setHelp('Clean cache');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $cachePools = $this->cacheManager->getCachePools();
            foreach ($cachePools as $cachePool) {
                $output->write('Clean "' . $cachePool . '": ');
                $cache = $this->cacheManager->getCache($cachePool);
                $cleared = $cache->clear();
                if ($cleared) {
                    $output->writeln('<info>Ok</info>');
                } else {
                    $output->writeln('<error>Error</error>');
                }
            }

            return 0;
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }

}
