<?php
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Project\Command\CommandManager;
use Attlaz\Project\Logger\Logger;
use Attlaz\Project\Logger\Processor;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Attlaz\Project\Serialization\SerializeTaskResult;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteTask extends Command
{
    private $commandManager;
    private $logger;

    public function __construct(CommandManager $commandManager, Logger $logger)
    {
        parent::__construct();

        $this->commandManager = $commandManager;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('task:execute')
             ->setDescription('Run task.')
             ->setHelp('This command allows you to run a task')
             ->addArgument('task', InputArgument::REQUIRED, 'Task (id) to execute')
             ->addOption('arguments', null, InputOption::VALUE_REQUIRED, 'How many times should the message be printed?', null)
             ->addOption('execution', null, InputOption::VALUE_REQUIRED, 'How many times should the message be printed?', 'some random string');
//        ->addArgument('arguments', InputArgument::REQUIRED, 'Arguments to pass to the command')
//        ->addArgument('execution', InputArgument::REQUIRED, 'Execution id to identify the execution');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
//        // outputs multiple lines to the console (adding "\n" at the end of each line)
//        $output->writeln([
//            'User Creator',
//            '============',
//            '',
//        ]);
//
//        // the value returned by someMethod() can be an iterator (https://secure.php.net/iterator)
//        // that generates and returns the messages with the 'yield' PHP keyword
//        //$output->writeln($this->someMethod());
//
//        // outputs a message followed by a "\n"
//        $output->writeln('Whoa!');
//
//        // outputs a message without adding a "\n" at the end of the line
//        $output->write('You are about to ');
//        $output->write('create a user.');

        try {
            $taskExecutionRequest = $this->getRequestFromInput($input);

            $logProcessor = new Processor();
            $logProcessor->setExecutionId($taskExecutionRequest->getExecutionId());
            $this->logger->pushProcessor($logProcessor);

            $taskExecutionResult = $this->commandManager->executeTask($taskExecutionRequest);

            $this->sendResponse($taskExecutionResult);

//            if ($taskExecutionResult->getSuccess()) {
//                exit(0);
//            } else {
//                //TODO: change exit code based on exception type
//                exit(1);
//            }
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());
            //exit(1);
        }
    }

    private function getRequestFromInput(InputInterface $input)
    {
        $task = $input->getArgument('task');
        $arguments = $this->getArguments($input);

        $executionId = $input->getOption('execution');

        return new TaskExecutionRequest($task, $arguments, $executionId);
    }

    private function getArguments(InputInterface $input): array
    {
        $arguments = $input->getOption('arguments');

        if (\is_null($arguments)) {
            return [];
        }

        try {
            $arguments = \base64_decode($arguments);
            if ($arguments === false) {
                throw new \Exception('Unable to decode arguments');
            }
            $arguments = \json_decode($arguments, true, \JSON_THROW_ON_ERROR);
            if (\is_null($arguments)) {
                throw new \Exception('Unable to decode arguments');
            }
        } catch (\Exception $ex) {
            throw new \Exception('Unable to read task arguments: ' . $ex->getMessage());
        }

        return $arguments;
    }

    private function sendResponse(TaskExecutionResult $taskExecutionResult)
    {
        $cmd = new SerializeTaskResult();
        $strTaskResult = $cmd->__invoke($taskExecutionResult);

        $this->logger->debug('Sending back response: ' . $strTaskResult);
        echo \base64_encode('Result') . ':' . base64_encode($strTaskResult);
    }
}