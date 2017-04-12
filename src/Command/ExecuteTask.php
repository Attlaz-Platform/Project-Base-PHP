<?php
declare(strict_types=1);

namespace Attlaz\Core\Command;

use Attlaz\Core\Job\DownloadFile;
use Attlaz\Core\Job\Log;
use Attlaz\Core\Job\Ping;
use Attlaz\Core\Model\Task;
use Attlaz\Core\Model\TaskResult;
use Psr\Log\LoggerInterface;

class ExecuteTask
{

    private const INVOKE_METHOD = '__invoke';
    private $jobs = [
        'download_file' => DownloadFile::class,
        'log'           => Log::class,
        'ping'          => Ping::class,
    ];

    public function __invoke(Task $task, LoggerInterface $logger = null): TaskResult
    {
        if ($logger) {
            $logger->debug('Execute task: ' . $task->getMethod());
        }

        try {
            $result = $this->executeTask($task);
        } catch (\Throwable $ex) {
            if ($logger) {
                $logger->error('Unable to complete task: ' . $ex->getMessage());
            }
            $result = new TaskResult($task, $ex->getMessage(), false);
        }

        return $result;

    }

    /**
     * @param Task $task
     * @return TaskResult
     * @throws \Exception
     */
    private function executeTask(Task $task): TaskResult
    {
        $jobClass = $this->getJobClass($task);

        $parameterValues = $this->getMethodArguments($task, $jobClass);

        $jobClassInstance = new $jobClass;
        $result = call_user_func_array([
            $jobClassInstance,
            self::INVOKE_METHOD,
        ], $parameterValues);

        return new TaskResult($task, $result, true);
    }

    private function getMethodArguments(Task $task, $jobClass): array
    {
        $m = new \ReflectionMethod($jobClass, self::INVOKE_METHOD);

        $parameterValues = [];
        $parameters = $m->getParameters();
        foreach ($parameters as $parameter) {

            $parameterValues[] = $this->getArgumentValue($task, $parameter);;
        }

        return $parameterValues;
    }

    /**
     * @param Task $task
     * @param \ReflectionParameter $parameter
     * @return mixed
     * @throws \Exception
     */
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

        return $parameterValue;
    }

    /**
     * @param Task $task
     * @return string
     * @throws \Exception
     */
    private function getJobClass(Task $task): string
    {
        $method = $task->getMethod();
        if (!isset($this->jobs[$method])) {
            throw new \Exception('Unknown method "' . $task->getMethod() . '"');

        }
        $jobClass = $this->jobs[$method];

        return $jobClass;
    }
}