<?php
declare(strict_types=1);

namespace Attlaz\Project\Helper;


use Attlaz\Framework\Model\JobCommand;
use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Project\Project;
use Psr\Log\LoggerInterface;

class ExecuteTaskHelper
{

    /** @var  LoggerInterface */
    private $logger;
    /** @var Project */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
        $this->logger = $project->getContainer()
                                ->get(LoggerInterface::class);
    }

    public function __invoke(Task $task): TaskResult
    {
        $this->logger->info('Execute task: ' . $task->getMethod() . ' (' . \json_encode($task->getArguments()) . ')');

        try {
            $result = $this->executeTask($task);

            $this->logger->info('Task: ' . $task->getMethod() . ' execution complete (' . \json_encode($task->getArguments()) . ')');
        } catch (\Throwable $ex) {
            $this->logger->error('Unable to complete task: ' . $ex->getMessage(), ['exception' => $ex]);

            $result = new TaskResult($task, $ex->getMessage(), false);
        }

        return $result;
    }

    private function executeTask(Task $task): TaskResult
    {
        $jobCommand = $this->getJobClass($task);

        $parameterValues = $this->getMethodArguments($task, $jobCommand);

        $result = call_user_func_array([
            $jobCommand,
            JobCommand::INVOKE_METHOD,
        ], $parameterValues);

        return new TaskResult($task, $result, true);
    }

    private function getMethodArguments(Task $task, JobCommand $jobClass): array
    {
        $m = new \ReflectionMethod($jobClass, JobCommand::INVOKE_METHOD);

        $parameterValues = [];
        $parameters = $m->getParameters();
        foreach ($parameters as $parameter) {
            $parameterValues[] = $this->getArgumentValue($task, $parameter);;
        }

        return $parameterValues;
    }

    private function getArgumentValue(Task $task, \ReflectionParameter $parameter)
    {
        $parameterName = $parameter->getName();

        if (!$task->hasArgument($parameterName) && !$parameter->isOptional()) {
            throw new \Exception('Missing parameter "' . $parameterName . '"');
        }

        if (!$task->hasArgument($parameterName) && $parameter->isOptional()) {
            $parameterValue = $parameter->getDefaultValue();
        } else {
            $parameterValue = $task->getArgument($parameterName);
        }

        if ($parameter->hasType()) {
            $this->validateParameterValueType($parameterValue, $parameter->getType());
        }

        return $parameterValue;
    }

    private function validateParameterValueType($value, \ReflectionType $type): void
    {
        $type = $type->getName();
        switch ($type) {
            case 'int':
                if (!\is_int($value)) {
                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
                }
                break;
            case 'string':
                if (!\is_string($value)) {
                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
                }
                break;
            case 'array':
                if (!\is_array($value)) {
                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
                }
                break;
            case 'bool':
                if (!\is_bool($value)) {
                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
                }
                break;
            default:
                $this->logger->warning('Unknown parameter type "' . $type . '"');
        }
    }

    private function getJobClass(Task $task): JobCommand
    {
        $commandName = $task->getMethod();

        if (!$this->project->hasCommand($commandName)) {
            throw new \Exception('Unable to execute command "' . $commandName . '": command not found');
        }

        $commandClass = $this->project->getCommandClass($commandName);

        $container = $this->project->getContainer();
        if (!$container->has($commandClass)) {
            throw new \Exception('Unable to execute command "' . $commandName . '": class not found');
        }

        $command = $container->get($commandClass);

        if (!$command instanceof JobCommand) {
            throw new \Exception('Unable to execute command "' . $commandName . '": must be ' . JobCommand::class);
        }

        $command->setLogger($this->logger);

        if (!method_exists($command, JobCommand::INVOKE_METHOD)) {
            throw new \Exception('Unable to execute command "' . $commandName . '": execute method does not exist');
        }

        return $command;
    }
}