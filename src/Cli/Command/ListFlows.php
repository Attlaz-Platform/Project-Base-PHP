<?php

declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Project\Command\CommandDefinition;
use Attlaz\Project\Command\CommandManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListFlows extends Command
{


    public function __construct(private CommandManager $commandManager)
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('flow:list')
            ->setDescription('List flows.')
            ->setHelp('This command allows you to list available flows');
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
                    $command->flowId,
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
        return 0;
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
