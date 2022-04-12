<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\AttlazMonolog\Handler\AttlazHandler;
use Attlaz\Model\Log\LogStreamId;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Logger\Logger;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class CommandManager
{
    private CommandDiscovery $discovery;
    private ContainerInterface $diContainer;
    private Environment $environment;
    private LoggerInterface $logger;
    private ?LogStreamId $previousLogStreamId = null;
    private ?LogLevel $previousLogLevel = null;

    public function initialize(
        CommandDiscovery   $discovery,
        ContainerInterface $diContainer,
        Environment        $environment,
        LoggerInterface    $logger
    )
    {
        //TODO: we should check if the CommandManager is initialized an has everything loaded
        $this->discovery = $discovery;
        $this->diContainer = $diContainer;
        $this->environment = $environment;
        $this->logger = $logger;
    }


    private function enableExecutionLogging(string $executionId): void
    {
        if ($this->logger instanceof Logger) {
            $handlers = $this->logger->getHandlers();
            foreach ($handlers as $handler) {
                if ($handler instanceof AttlazHandler) {
                    $this->previousLogStreamId = $handler->getLogStreamId();
                    $this->previousLogLevel = $handler->getLevel();
                    $handler->setLogStreamId(new LogStreamId('execution:' . $executionId));
                    $handler->setLevel(LogLevel::DEBUG);
                }
            }
        }
    }

    private function disableExecutionLogging(): void
    {
        if ($this->previousLogStreamId !== null && $this->logger instanceof Logger) {
            $handlers = $this->logger->getHandlers();
            foreach ($handlers as $handler) {
                if ($handler instanceof AttlazHandler) {
                    $handler->setLogStreamId($this->previousLogStreamId);
                    $handler->setLevel($this->previousLogLevel);
                }
            }
        }
    }

    public function executeTask(TaskExecutionRequest $request): TaskExecutionResult
    {
        //TODO: make it possible to switch between app/staging app
        $taskKey = $request->getTask();
        $taskExecutionKey = $request->getExecutionId();

        $urlSegments = [
            'tasks',
            $taskKey,
            'execution',
            $taskExecutionKey,
        ];
        $dashboardUrl = $this->environment->getAppUrl(null, $urlSegments);
        // Only log this to console
        $this->logger->info('More info: ' . $dashboardUrl, [AttlazHandler::CONTEXT_SKIP => true]);
        $this->enableExecutionLogging($request->getExecutionId());

        $context = [];
        if (count($request->getArguments()) > 0) {
            $context['arguments'] = $request->getArguments();
        }
        $this->logger->info('Execution started', $context);

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

            $this->logger->info('Execution complete', $context);

        } catch (\Throwable $ex) {
            $context['error'] = $ex;
            $this->logger->error('Execution failed (' . $ex->getMessage() . ')', $context);
            $result = new TaskExecutionResult($request->getTask(), $ex->getMessage(), false);
        }

        $this->disableExecutionLogging();
        $this->logger->info('More info: ' . $dashboardUrl, [AttlazHandler::CONTEXT_SKIP => true]);
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
            $parameterType = $parameter->hasType() ? $parameter->getType() : 'undefined';
            $strErrorMessage = 'Missing parameter "' . $parameterName . '" (type ' . $parameterType . ')';
            throw new \Exception($strErrorMessage);
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
        if (!CommandParameterDefinition::isCorrectType($value, $parameter)) {
            $parameterName = $parameter->getName();
            $parameterType = $parameter->hasType() ? $parameter->getType() : 'undefined';
            $strErrorMessage = 'Parameter "' . $parameterName . '" has invalid type "' . $parameterType . '" expected';
            throw new \Exception($strErrorMessage);
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
