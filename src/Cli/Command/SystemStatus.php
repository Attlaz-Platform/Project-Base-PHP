<?php

declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SystemStatus extends Command
{


    public function __construct(private Client $client, private Environment $environment, private LoggerInterface $logger)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('system:status')
            ->setDescription('Get the status')
            ->setHelp('Use this command to get the status');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->environment->isInitialized()) {
            $output->writeln('The project is not initialized');
        } else {
            $output->writeln('Project: ' . $this->environment->getProject()->name);
            $output->writeln('Environment: ' . $this->environment->getProjectEnvironment()->name);
        }

        return 1;
    }
}
