<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunTests extends Command
{
    public function __construct(protected LoggerInterface $logger)
    {
        parent::__construct();


    }

    protected function configure()
    {
        $this->setName('test:run')
            ->setDescription('Run tests')
            ->setHelp('This command allows you to run tests');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        return 1;
    }
}
