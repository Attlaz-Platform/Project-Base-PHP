<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Project\App\Environment;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SystemStatus extends Command
{
    public function __construct(private readonly Environment $environment)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('project:status')
            ->setDescription('Get the status')
            ->setHelp('Use this command to get an overview of this project');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Project status');
        if (!$this->environment->isInitialized()) {
            $io->text([
                'This project is not initialized',
                '',
                'Following environment variables',
                '`' . Environment::ENV_PROJECT_ENVIRONMENT . '=[your environment id]`',
                '',
                'Authenticate with the api through a token:',
                '`' . Environment::ENV_API_TOKEN . '=[your access token]`',
            ]);
        } else {
            $io->text([
                'This project is initialized',
                '',
            ]);
            $io->listing([
                'Project: ' . $this->environment->getProject()->name,
                'Environment: ' . $this->environment->getProjectEnvironment()->name,
            ]);
        }
        $io->text(['']);
        $io->listing([
            'Dashboard: <href=' . $this->environment->getDashboardUrl() . '>' . $this->environment->getDashboardUrl() . '</>',
        ]);
        return 1;
    }
}
