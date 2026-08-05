<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\AttlazMonolog\Formatter\AttlazFormatter;
use Attlaz\AttlazMonolog\Handler\AttlazHandler;
use Attlaz\Client;
use Attlaz\Model\FlowRun;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Logger\Logger;
use Attlaz\Project\Model\FlowRunRequest;
use Attlaz\Project\Model\FlowRunResult;
use Monolog\Handler\HandlerInterface;
use Monolog\Level;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CommandManager
{
    private FlowCommandDiscovery $discovery;
    private ContainerInterface $diContainer;
    private Environment $environment;
    private LoggerInterface $logger;
    private AttlazHandler|null $flowRunLogHandler = null;

    public function initialize(
        FlowCommandDiscovery $discovery,
        ContainerInterface   $diContainer,
        Environment          $environment,
        LoggerInterface      $logger
    ): void
    {
        //TODO: we should check if the CommandManager is initialized an has everything loaded
        $this->discovery = $discovery;
        $this->diContainer = $diContainer;
        $this->environment = $environment;
        $this->logger = $logger;
    }

    public function runFlow(FlowRunRequest $request): FlowRunResult
    {
        //TODO: make it possible to switch between app/staging app
        $flowKey = $request->getFlowRun()->flowId;
        $flowRunId = $request->getFlowRun()->id;

        $urlSegments = [
            'flows',
            $flowKey,
            'run',
            $flowRunId,
        ];
        $dashboardUrl = $this->environment->getAppUrl(null, $urlSegments);
        // Only log this to console
        $this->logger->info('More info: ' . $dashboardUrl, [AttlazHandler::CONTEXT_SKIP => true]);
        $this->enableFlowRunLogging($request->getFlowRun(), $request->verboseLogging);

        $context = [];
        if (\count($request->getArguments()) > 0) {
            $context['arguments'] = $request->getArguments();
        }
        $this->logger->info('Execution started', $context);

        try {
            $commandDefinition = $this->getCommandDefinitionByFlow($request);

            $parameterValues = $this->getMethodArguments($request, $commandDefinition);

            $commandInstance = $this->getCommandInstance($commandDefinition);

            $commandInstance->init();

            $result = \call_user_func_array([
                $commandInstance,
                AbstractCommand::INVOKE_METHOD,
            ], $parameterValues);

            $result = new FlowRunResult($request->getFlowRun()->flowId, $result, true);

            $this->logger->info('Execution complete', $context);

        } catch (\Throwable $ex) {
            $context['error'] = $ex;
            $this->logger->error('Execution failed (' . $ex->getMessage() . ')', $context);
            $result = new FlowRunResult($request->getFlowRun()->flowId, $ex->getMessage(), false);
        } finally {
            // Clean up all connections opened during this flow run
            if (isset($commandInstance)) {
                $commandInstance->getConnectionPool()->disconnectAll();
            }


        }

        $this->disableFlowRunLogging();
        $this->logger->info('More info: ' . $dashboardUrl, [AttlazHandler::CONTEXT_SKIP => true]);
        return $result;
    }

    /**
     * Returns all the commands
     * @return CommandDefinition[]
     */
    public function getCommandDefinitions(): array
    {
        return $this->discovery->getCommands();
    }

    /**
     * Send this run's log to the API, on the run's own log stream.
     *
     * The handler is attached here rather than at start-up because the stream id only exists once
     * there is a run. Before, a handler was created at start-up addressed to `environment:<id>` and
     * retargeted here; that identifier form was retired in May 2025 and the API discards it.
     */
    private function enableFlowRunLogging(FlowRun $flowRun, bool $verboseLogging): void
    {
        if (!$this->logger instanceof Logger) {
            return;
        }

        $handler = new AttlazHandler(
            $this->diContainer->get(Client::class),
            $flowRun->logStreamId,
            $verboseLogging ? Level::Debug : Level::Info,
        );
        $handler->setFormatter(new AttlazFormatter());

        $this->flowRunLogHandler = $handler;
        $this->logger->pushHandler($handler);
    }

    private function disableFlowRunLogging(): void
    {
        if ($this->flowRunLogHandler === null || !$this->logger instanceof Logger) {
            return;
        }

        // Filter by identity rather than popHandler(), so a handler pushed by the flow itself while
        // it ran is not removed instead of this one.
        $remaining = \array_filter(
            $this->logger->getHandlers(),
            fn(HandlerInterface $handler): bool => $handler !== $this->flowRunLogHandler,
        );
        $this->logger->setHandlers(\array_values($remaining));
        $this->flowRunLogHandler = null;
    }

    private function getCommandDefinitionByFlow(FlowRunRequest $flowRunRequest): CommandDefinition
    {
        $commands = $this->discovery->getCommands();

        foreach ($commands as $command) {
            if ($command->flowId === $flowRunRequest->getFlowRun()->flowId) {
                return $command;
            }
        }
        throw new \Exception('No command found for flow "' . $flowRunRequest->getFlowRun()->flowId . '"');
    }

    /**
     * @return list<mixed> positional argument values for the command method
     */
    private function getMethodArguments(FlowRunRequest $request, CommandDefinition $commandDefinition): array
    {
        $parameterValues = [];
        $commandDefinitionParameters = $commandDefinition->getParameters();
        foreach ($commandDefinitionParameters as $commandDefinitionParameter) {
            $parameterValues[] = $this->getArgumentValue($request, $commandDefinitionParameter);
        }

        return $parameterValues;
    }

    private function getArgumentValue(FlowRunRequest $request, CommandParameterDefinition $parameter): mixed
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
                $parameterValue = CommandParameterDefinition::coerce($parameterValue, $parameter);
                $this->validateParameterValueType($parameterValue, $parameter);
            }
        }

        return $parameterValue;
    }

    private function validateParameterValueType(mixed $value, CommandParameterDefinition $parameter): void
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
}
