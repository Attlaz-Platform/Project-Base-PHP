<?php /** @noinspection PhpMissingParentCallCommonInspection */
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Project\Command\CommandManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListTasks extends Command
{
    private $commandManager;
    private $logger;

    public function __construct(CommandManager $commandManager, LoggerInterface $logger)
    {
        parent::__construct();

        $this->commandManager = $commandManager;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('task:list')
             ->setDescription('List tasks.')
             ->setHelp('This command allows you to list available tasks');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commands = $this->commandManager->getCommandDefinitions();

        $output->writeln('Available commands (' . count($commands) . '):');

        foreach ($commands as $command) {
            $output->writeln('[' . $command->task . '] ' . $command->className . '');
            //TODO: add parameter information
        }
    }
}
