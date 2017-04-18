<?php
declare(strict_types=1);

namespace Attlaz\Worker\Helper;

use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Worker\Job\DownloadFile;
use Attlaz\Worker\Job\DownloadFiles;
use Attlaz\Worker\Job\Example;
use Attlaz\Worker\Job\Log;
use Attlaz\Worker\Job\Loop;
use Attlaz\Worker\Job\Ping;
use Attlaz\Worker\Job\Wait;
use Attlaz\Worker\Job\Wait2;
use Psr\Log\LoggerInterface;

class ExecuteTaskHelper
{

    private const INVOKE_METHOD = '__invoke';
    private $jobs = [
        'download_file'  => DownloadFile::class,
        'log'            => Log::class,
        'ping'           => Ping::class,
        'example'        => Example::class,
        'wait'           => Wait::class,
        'wait2'          => Wait2::class,
        'download_files' => DownloadFiles::class,
        'loop'           => Loop::class,
    ];
    /** @var  LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {

        $this->logger = $logger;
    }

    public function __invoke(Task $task): TaskResult
    {


        $this->logger->info('Execute task: ' . $task->getMethod() . ' (' . \json_encode($task->getArguments()) . ')');

        try {
            $result = $this->executeTask($task);
        } catch (\Throwable $ex) {

            $this->logger->error('Unable to complete task: ' . $ex->getMessage());

            $result = new TaskResult($task, $ex->getMessage(), false);
        }

        return $result;

    }

    private function executeTask(Task $task): TaskResult
    {
        $jobClass = $this->getJobClass($task);

        $parameterValues = $this->getMethodArguments($task, $jobClass);

        $jobClassInstance = new $jobClass();
        $result = call_user_func_array([
            $jobClassInstance,
            self::INVOKE_METHOD,
        ], $parameterValues);

        return new TaskResult($task, $result, true);
    }

    private function getMethodArguments(Task $task, string $jobClass): array
    {
        $m = new \ReflectionMethod($jobClass, self::INVOKE_METHOD);

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
            default:
                $this->logger->warning('Unknown parameter type "' . $type . '"');
        }
    }

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