<?php
declare(strict_types=1);

namespace Attlaz\Project\Cli\Command;

use Attlaz\Project\Logger\Logger;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\TaskExecution\CLI;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteTask extends Command
{
    protected $taskExecutor;
    protected $logger;

    public function __construct(CLI $taskExecutor, Logger $logger)
    {
        parent::__construct();

        $this->taskExecutor = $taskExecutor;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this->setName('task:execute')
             ->setDescription('Run task.')
             ->setHelp('This command allows you to run a task')
             ->addArgument('task', InputArgument::REQUIRED, 'Task (id) to execute')
             ->addOption('arguments', null, InputOption::VALUE_REQUIRED, '', null)
             ->addOption('execution', null, InputOption::VALUE_REQUIRED, '', 'x');
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $taskExecutionRequest = $this->getRequestFromInput($input);

            return $this->taskExecutor->execute($taskExecutionRequest);
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());

            return 1;
        }
    }

    private function getRequestFromInput(InputInterface $input): TaskExecutionRequest
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
            //PHP 7.3 \JSON_THROW_ON_ERROR
            $arguments = \json_decode($arguments, true);
            if ($arguments === false || \is_null($arguments)) {
                throw new \Exception('Unable to decode arguments');
            }
        } catch (\Exception $ex) {
            throw new \Exception('Unable to read task arguments: ' . $ex->getMessage());
        }

        return $arguments;
    }

}
