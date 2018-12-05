<?php
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Project\Command\CommandParameterDefinition;
use Attlaz\Project\Model\TaskExecutionRequest;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class ExecuteTaskInteractive extends ExecuteTask
{

    protected function configure()
    {
        $this->setName('task:execute:interactive')
             ->setDescription('Run task.')
             ->setHelp('This command allows you to run a task interactively');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $commands = $this->commandManager->getCommandDefinitions();

            $taskIds = [];
            foreach ($commands as $command) {
                $taskIds[$command->task] = '' . $command->className . '';
            }

            $questionHelper = new QuestionHelper();

            /**
             * Request taskId
             */
            $question = new ChoiceQuestion('Please select a task:', $taskIds);
            $question->setErrorMessage('Task %s is invalid.');

            $taskId = $questionHelper->ask($input, $output, $question);

            $output->writeln('You have just selected: ' . $taskId);

            $selectedCommand = null;
            foreach ($commands as $command) {
                if ($taskId === $command->task) {
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

                $question = new Question('Please enter a value for: ' . $parameterString . ':', $parameter->getDefault());

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

            $taskExecutionRequest = new TaskExecutionRequest($taskId, $parameterValues, 'soe');

            return $this->executeTaskExecutionRequest($taskExecutionRequest);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }

    private function formatValue($value, CommandParameterDefinition $parameter)
    {
        if ($parameter->hasType()) {
            if ($parameter->getType() === 'int') {
                if (\is_numeric($value)) {
                    $value = intval($value);
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
            }
        }

        return $value;
    }
}
