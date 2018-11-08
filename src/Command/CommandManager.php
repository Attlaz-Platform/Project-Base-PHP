<?php
declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CommandManager
{
    private $discovery;
    private $diContainer;
    private $logger;

    public function __construct(CommandDiscovery $discovery, ContainerInterface $diContainer, LoggerInterface $logger)
    {
        $this->discovery = $discovery;
        $this->diContainer = $diContainer;
        $this->logger = $logger;
    }

    public function executeTask(TaskExecutionRequest $request): TaskExecutionResult
    {
        $this->logger->info('Execute task: ' . $request->getTask() . ' (' . \json_encode($request->getArguments()) . ')');

        try {
            $commandDefinition = $this->getCommandDefinitionByTask($request);

            $parameterValues = $this->getMethodArguments($request, $commandDefinition);

            $commandInstance = $this->getCommandInstance($commandDefinition);

            $result = call_user_func_array([
                $commandInstance,
                AbstractCommand::INVOKE_METHOD,
            ], $parameterValues);

            $result = new TaskExecutionResult($request->getTask(), $result, true);

            $this->logger->info('Task: ' . $request->getTask() . ' execution complete (' . \json_encode($request->getArguments()) . ')');
        } catch (\Throwable $ex) {
            $this->logger->error('Unable to complete task: ' . $ex->getMessage(), ['exception' => $ex]);

            $result = new TaskExecutionResult($request->getTask(), $ex->getMessage(), false);
        }

        return $result;
    }

    private function getCommandDefinitionByTask(TaskExecutionRequest $taskExecutionRequest): CommandDefinition
    {
        $commands = $this->discovery->getCommands();

        foreach ($commands as $command) {
            if ($command->task === $taskExecutionRequest->getTask()) {
                return $command;
            }
        }
        throw new \Exception('No command found for task "' . $taskExecutionRequest->getTask() . '"');
    }

    private function getMethodArguments(TaskExecutionRequest $request, CommandDefinition $commandDefinition): array
    {
        $parameterValues = [];
        $commandDefinitionParameters = $commandDefinition->getParameters();
        foreach ($commandDefinitionParameters as $commandDefinitionParameter) {
            $parameterValues[] = $this->getArgumentValue($request, $commandDefinitionParameter);
        }

        return $parameterValues;
    }

    private function getArgumentValue(TaskExecutionRequest $request, CommandParameterDefinition $parameter)
    {
        $parameterName = $parameter->getName();

        if (!$request->hasArgument($parameterName) && $parameter->isRequired()) {
            throw new \Exception('Missing parameter "' . $parameterName . '" (type ' . ($parameter->hasType() ? $parameter->getType() : 'undefined') . ')');
        }

        if (!$request->hasArgument($parameterName) && !$parameter->isRequired()) {
            $parameterValue = $parameter->getDefault();
        } else {
            $parameterValue = $request->getArgument($parameterName);
            if ($parameter->hasType()) {
                $this->validateParameterValueType($parameterValue, $parameter);
            }
        }

        return $parameterValue;
    }

    private function validateParameterValueType($value, CommandParameterDefinition $parameter): void
    {
        $valueType = \gettype($value);
        if ($valueType !== $parameter->getType()) {
            throw new \Exception('Parameter "' . $parameter->getName() . '" has invalid type "' . $valueType . '", type "' . ($parameter->hasType() ? $parameter->getType() : 'undefined') . '" expected');
        }

//        switch ($parameter->getType()) {
//            case 'int':
//                if (!\is_int($value)) {
//                    throw new \Exception('Parameter "' . $parameter->getName() . '" has invalid type, type "' . ($parameter->hasType() ? $parameter->getType() : 'undefined') . '" expected');
//                }
//                break;
//            case 'string':
//                if (!\is_string($value)) {
//                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
//                }
//                break;
//            case 'array':
//                if (!\is_array($value)) {
//                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
//                }
//                break;
//            case 'bool':
//                if (!\is_bool($value)) {
//                    throw new \Exception('Invalid parameter type, "' . $type . '" expected');
//                }
//                break;
//            default:
//                $this->logger->warning('Unknown parameter type "' . $type . '"');
//        }
    }

    private function getCommandInstance(CommandDefinition $commandDefinition): AbstractCommand
    {
//
//        $container = $this->project->getDIContainer();
        if (!$this->diContainer->has($commandDefinition->className)) {
            throw new \Exception('Unable to execute command "' . $commandDefinition->className . '": class not found');
        }
//
        $command = $this->diContainer->get($commandDefinition->className);
//
//        if (!$command instanceof AbstractCommand) {
//            throw new \Exception('Unable to execute command "' . $commandName . '": must be ' . AbstractCommand::class);
//        }
//
        $command->setLogger($this->logger);
//
//        if (!method_exists($command, AbstractCommand::INVOKE_METHOD)) {
//            throw new \Exception('Unable to execute command "' . $commandName . '": execute method does not exist');
//        }
//
        return $command;
    }

    /**
     * Returns all the commands
     * @return CommandDefinition[]
     */
    public function getCommandDefinitions(): array
    {
        return $this->discovery->getCommands();
    }

}