<?php /** @noinspection PhpMissingParentCallCommonInspection */
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Project\Command\CommandDefinition;
use Attlaz\Project\Command\CommandManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
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
        $output->writeln('<info>Available commands:</info>');
        $commands = $this->commandManager->getCommandDefinitions();

        if (count($commands) === 0) {
            $output->writeln('<error>No commands found</error>');
        } else {
            $rows = [];
            foreach ($commands as $command) {
                $parameters = $this->formatParameters($command);

                $rows[] = [
                    $command->task,
                    $command->className,
                    new TableCell(\implode("\n", $parameters), ['rowspan' => count($parameters)]),
                ];
            }

            $table = new Table($output);
            $table->setStyle('box');
            $table->setHeaders([
                'Id',
                'Class',
                'Parameters',
            ])
                  ->setRows($rows);
            $table->render();
        }
    }

    private function formatParameters(CommandDefinition $command): array
    {
        $result = [];
        $parameterDefinitions = $command->getParameters();
        foreach ($parameterDefinitions as $parameterDefinition) {
            $result[] = $parameterDefinition->__toString();
        }

        return $result;
    }
}
