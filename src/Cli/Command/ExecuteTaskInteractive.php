<?php
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

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
                $parameterString = '\'' . $parameter->getName() . '\'';
                if ($parameter->hasType()) {
                    $parameterString .= ' (' . $parameter->getType() . ')';
                } else {
                    $parameterString .= ' (No type specified)';
                }

                if ($parameter->isRequired()) {
                    $parameterString .= ' [required]';
                } else {
                    $parameterString .= ' [default: ' . $parameter->getDefault() . ']';
                }

                $question = new Question('Please enter a value for parameter ' . $parameterString . ':', $parameter->getDefault());

                $parameterValue = $questionHelper->ask($input, $output, $question);
//TODO: validate input
                $output->writeln('You have just selected: ' . $parameterValue);

                $parameterValues[$parameter->getName()] = $parameterValue;
            }

            $taskExecutionRequest = new TaskExecutionRequest($taskId, $parameterValues, 'soe');

            return $this->executeTaskExecutionRequest($taskExecutionRequest);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }
}
