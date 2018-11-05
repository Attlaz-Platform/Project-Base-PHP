<?php
declare(strict_types=1);

namespace Attlaz\Project\Helper;

use Attlaz\Project\Model\TaskExecutionRequest;

class TaskExecutionRequestHelper
{
    private const COMMAND_PARAM_SHORT = 'c';
    private const COMMAND_PARAM_LONG = 'command';

    private const ARGUMENTS_PARAM_SHORT = 'p';
    private const ARGUMENTS_PARAM_LONG = 'params';

    private const EXECUTION_PARAM_SHORT = 'e';
    private const EXECUTION_PARAM_LONG = 'execution';

    public static function getRequest(): TaskExecutionRequest
    {
        $command = self::getCommand();
        $arguments = self::getArguments();
        $execution = self::getExecution();

        return new TaskExecutionRequest($command, $command, $arguments, $execution);
    }

    private static function getCommand(): string
    {
        $command = self::getCLIOption(self::COMMAND_PARAM_SHORT, self::COMMAND_PARAM_LONG);

        if (\is_null($command)) {
            throw new \Exception('Invalid request: task execution request not defined');
        }

        return (string)$command;
    }

    private static function getArguments(): array
    {
        $arguments = self::getCLIOption(self::ARGUMENTS_PARAM_SHORT, self::ARGUMENTS_PARAM_LONG);

        if (\is_null($arguments)) {
            return [];
        }

        $arguments = \base64_decode($arguments);
        $arguments = \json_decode($arguments, true);

        return $arguments;
    }

    private static function getExecution(): string
    {
        $execution = self::getCLIOption(self::EXECUTION_PARAM_SHORT, self::EXECUTION_PARAM_LONG);

        if (\is_null($execution)) {
            throw new \Exception('Invalid request: execution not defined');
        }

        return (string)$execution;
    }

    private static function getCLIOption(string $short, string $long)
    {
        $options = getopt('c:p:e::');

        if (isset($options[$short])) {
            return $options[$short];
        }
        if (isset($options[$long])) {
            return $options[$long];
        }

        return null;
    }
}