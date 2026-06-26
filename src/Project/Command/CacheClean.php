<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Project\App\Config;
use Attlaz\Project\Storage\StorageManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CacheClean extends Command
{
    public function __construct(
        protected readonly Config          $config,
        protected readonly StorageManager  $storageManager,
        protected readonly LoggerInterface $logger
    )
    {
        parent::__construct();


    }

    #[\Override]
    protected function configure(): void
    {
        $this->setName('cache:clean')
            ->setAliases([
                'cache:clear',
                'cache:flush',
            ])
            ->setDescription('Clean cache')
            ->setHelp('Clean cache');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $cache = $this->storageManager->cache;

            $cacheBucketKeys = $cache->getBucketKeys();
            foreach ($cacheBucketKeys as $cacheBucketKey) {
                $output->write('Clean cache <comment>' . $cacheBucketKey . '</comment>: ');

                $cleared = $cache->clearBucket($cacheBucketKey);
                if ($cleared) {
                    $output->writeln('<info>Done</info>');
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
