<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Client;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Command\CommandManager;
use Attlaz\Project\Command\CommandParameterDefinition;
use Attlaz\Project\FlowRun\CLI;
use Attlaz\Project\Model\FlowRunRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class RunFlowInteractive extends RunFlow
{
    public function __construct(
        CLI                             $flowRunHandler,
        Client                          $client,
        private readonly CommandManager $commandManager,
        Environment                     $environment,
        LoggerInterface                 $logger
    )
    {
        parent::__construct($flowRunHandler, $client, $environment, $logger);
    }

    protected function configure()
    {
        $this->setName('flow:run:interactive')
            ->setDescription('Run flow interactively.')
            ->setHelp('This command allows you to run a flow interactively');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input);
        try {
            $commands = $this->commandManager->getCommandDefinitions();

            $flowIds = [];
            foreach ($commands as $command) {
                $flowIds[$command->flowId] = $command->className;
            }

            $questionHelper = new QuestionHelper();

            /**
             * Request flowId
             */
            $question = new ChoiceQuestion('Please select a flow:', $flowIds);
            $question->setErrorMessage('Flow %s is invalid.');

            $flowId = $questionHelper->ask($input, $output, $question);

            $output->writeln('You have just selected: ' . $flowId);

            $selectedCommand = null;
            foreach ($commands as $command) {
                if ($flowId === $command->flowId) {
                    $selectedCommand = $command;
                }
            }
            /**
             * Request parameters
             */

            $parameterValues = [];
            $parameters = $selectedCommand->getParameters();

            foreach ($parameters as $parameter) {
                $parameterString = $parameter->__toString();
                $parameterValue = null;
                $strQuestionText = 'Please enter a value for: ' . $parameterString . ':';
                $question = new Question($strQuestionText, $parameter->getDefault());

                $valid = false;
                while (!$valid) {
                    $parameterValue = $questionHelper->ask($input, $output, $question);
                    $parameterValue = $this->formatValue($parameterValue, $parameter);
                    $valid = CommandParameterDefinition::isCorrectType($parameterValue, $parameter);
                    if (!$valid) {
                        $output->writeln('<error>Invalid value</error>');
                    }
                }

                //TODO: validate input
                // $output->writeln('You have just selected: ' . $parameterValue);

                $parameterValues[$parameter->getName()] = $parameterValue;
            }

            $flowRunRequest = new FlowRunRequest($flowId, $parameterValues, 'soe');

            return $this->flowRunHandler->execute($flowRunRequest);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }

    private function formatValue(mixed $value, CommandParameterDefinition $parameter): mixed
    {
        if ($parameter->hasType()) {
            if ($parameter->getType() === 'int') {
                if (\is_numeric($value)) {
                    $value = (int)$value;
                }
            } elseif ($parameter->getType() === 'bool') {
                $true = [
                    true,
                    'true',
                    'yes',
                    'y',
                    '1',
                    1,
                ];
                $false = [
                    false,
                    'false',
                    'no',
                    'n',
                    '0',
                    0,
                ];
                $matchValue = $value;
                if (\is_string($matchValue)) {
                    $matchValue = \strtolower($matchValue);
                }

                if (\in_array($matchValue, $true)) {
                    $value = true;
                } elseif (\in_array($matchValue, $false)) {
                    $value = false;
                }
            } elseif ($parameter->getType() === 'array') {
                if (!\is_null($value)) {
                    $value = \explode(',', $value);
                }
            }
        }

        return $value;
    }
}
