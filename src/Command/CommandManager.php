<?php
declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Project\App\Environment;
use Attlaz\Project\Logger\Logger;
use Attlaz\Project\Logger\Processor;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class CommandManager
{
    private $discovery;
    private $diContainer;
    private $environment;
    private $logger;

    //    public function __construct(
    //        CommandDiscovery $discovery,
    //        ContainerInterface $diContainer,
    //        Environment $environment,
    //        LoggerInterface $logger
    //    ) {
    //        $this->discovery = $discovery;
    //        $this->diContainer = $diContainer;
    //        $this->environment = $environment;
    //        $this->logger = $logger;
    //    }

    public function initialize(
        CommandDiscovery $discovery,
        ContainerInterface $diContainer,
        Environment $environment,
        LoggerInterface $logger
    ) {
        //TODO: we should check if the CommandManager is initialized an has everything loaded
        $this->discovery = $discovery;
        $this->diContainer = $diContainer;
        $this->environment = $environment;
        $this->logger = $logger;
    }

    public function executeTask(TaskExecutionRequest $request): TaskExecutionResult
    {
        if ($this->logger instanceof Logger) {
            $logProcessor = new Processor();
            $logProcessor->setExecutionId($request->getExecutionId());
            $this->logger->pushProcessor($logProcessor);
        }

        //TODO: make it possible to switch between app/staging app

        $teamKey = $this->environment->getProject()->team;
        $projectKey = $this->environment->getProject()->key;
        $environmentKey = $this->environment->getProjectEnvironment()->key;
        $taskKey = $request->getTask();
        $taskExecutionKey = $request->getExecutionId();
        $dashboardUrl = 'https://app.attlaz.com/' . $teamKey . '/' . $projectKey . '/' . $environmentKey . '/tasks/' . $taskKey . '/execution/' . $taskExecutionKey;

        $strLogMessage = 'Execute task: ' . $request->getTask() . ' (' . \json_encode($request->getArguments()) . ') ' . $dashboardUrl;

        $this->logger->info($strLogMessage);

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

            $strArguments = \json_encode($request->getArguments());
            $strLogMessage = 'Task: ' . $request->getTask() . ' execution complete (' . $strArguments . ') ' . $dashboardUrl;
            $this->logger->info($strLogMessage);
        } catch (\Throwable $ex) {
            $this->logger->error($ex);
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
