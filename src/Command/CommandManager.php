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

            $commandInstance->init();

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
       if(!CommandParameterDefinition::isCorrectType($value,$parameter))
       {
           throw new \Exception('Parameter "' . $parameter->getName() . '" has invalid type "' . ($parameter->hasType() ? $parameter->getType() : 'undefined') . '" expected');
       }
    }

    private function getCommandInstance(CommandDefinition $commandDefinition): AbstractCommand
    {
        if (!$this->diContainer->has($commandDefinition->className)) {
            throw new \Exception('Unable to execute command "' . $commandDefinition->className . '": class not found');
        }
        /** @var AbstractCommand $command */
        $command = $this->diContainer->get($commandDefinition->className);

        //TODO: should we do any validation on this?
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
