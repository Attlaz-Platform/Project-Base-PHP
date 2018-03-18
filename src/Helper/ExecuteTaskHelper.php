<?php
declare(strict_types=1);

namespace Attlaz\Project\Helper;

use Attlaz\Project\Model\JobCommand;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
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

    public function __invoke(TaskExecutionRequest $request): TaskExecutionResult
    {
        $this->logger->info('Execute task: ' . $request->getTask() . ' (' . \json_encode($request->getArguments()) . ')');

        try {
            $result = $this->executeTask($request);

            $this->logger->info('Task: ' . $request->getTask() . ' execution complete (' . \json_encode($request->getArguments()) . ')');
        } catch (\Throwable $ex) {
            $this->logger->error('Unable to complete task: ' . $ex->getMessage(), ['exception' => $ex]);

            $result = new TaskExecutionResult($request->getTask(), $ex->getMessage(), false);
        }

        return $result;
    }

    private function executeTask(TaskExecutionRequest $request): TaskExecutionResult
    {
        $jobCommand = $this->getJobClass($request);

        $parameterValues = $this->getMethodArguments($request, $jobCommand);

        $result = call_user_func_array([
            $jobCommand,
            JobCommand::INVOKE_METHOD,
        ], $parameterValues);

        return new TaskExecutionResult($request->getTask(), $result, true);
    }

    private function getMethodArguments(TaskExecutionRequest $request, JobCommand $jobClass): array
    {
        $m = new \ReflectionMethod($jobClass, JobCommand::INVOKE_METHOD);

        $parameterValues = [];
        $parameters = $m->getParameters();
        foreach ($parameters as $parameter) {
            $parameterValues[] = $this->getArgumentValue($request, $parameter);;
        }

        return $parameterValues;
    }

    private function getArgumentValue(TaskExecutionRequest $request, \ReflectionParameter $parameter)
    {
        $parameterName = $parameter->getName();

        if (!$request->hasArgument($parameterName) && !$parameter->isOptional()) {
            throw new \Exception('Missing parameter "' . $parameterName . '"');
        }

        if (!$request->hasArgument($parameterName) && $parameter->isOptional()) {
            $parameterValue = $parameter->getDefaultValue();
        } else {
            $parameterValue = $request->getArgument($parameterName);
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

    private function getJobClass(TaskExecutionRequest $task): JobCommand
    {
        $commandName = $task->getTask();

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