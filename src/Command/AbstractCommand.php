<?php

declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Client as AttlazClient;
use Attlaz\Project\App\Config;
use Attlaz\Project\App\Environment;
use Attlaz\Project\Connections\ConnectionPool;
use Attlaz\Project\Helper\OutputHelper;
use Attlaz\Project\Helper\Profiler;
use Attlaz\Project\Model\FlowRunRequest;
use Attlaz\Project\Model\FlowRunResult;
use Attlaz\Project\Storage\StorageManager;
use DI\Container as DIContainer;
use Psr\Log\LoggerInterface;

/**
 * show off @method
 *
 * @method execute()
 */
abstract class AbstractCommand
{
    public const INVOKE_METHOD = 'execute';
    private AttlazClient $attlazClient;
    protected LoggerInterface $logger;
    protected Environment $environment;
    protected Config $config;
    protected StorageManager $storageManager;
    protected DIContainer $dependencyManager;
    protected OutputHelper $outputHelper;
    protected ConnectionPool $connectionPool;
    protected Profiler $profiler;

    public function __construct(CommandContext $context)
    {
        $this->logger = $context->getLogger();
        $this->environment = $context->getEnvironment();
        $this->config = $context->getConfig();
        $this->storageManager = $context->getStorageManager();
        $this->dependencyManager = $context->getDependencyManager();
        $this->outputHelper = $context->getOutputHelper();
        $this->connectionPool = $context->getConnectionPool();
        $this->profiler = $context->getProfiler();

        $this->attlazClient = $this->dependencyManager->get(AttlazClient::class);
    }

    /**
     * Place code here to initialize that doesn't belong in the constructor.
     * For instance stuff handling the logger which is not available in the constructor
     */
    public function init()
    {
    }

    public function progress(string $key, int $current, int $total, string $label): void
    {
        $this->outputHelper->progress($key, $current, $total, $label);
    }


    public function startProfile(string $key, string $label = ''): void
    {
        $this->profiler->startProfile($key, $label);
    }

    public function endProfile(string $key): void
    {
        $this->profiler->endProfile($key);
    }

    public function getProfile(string $key): array
    {
        return $this->profiler->getProfile($key);
    }

//    /** @deprecated */
//    final protected function sendTaskWithResult(Task $task, string $branch): TaskExecutionResult
//    {
//        $request = $this->createRequest($task, $branch);
//        /** @var ResponseInterface $response */
//        $response = $this->getHTTPClient()
//            ->send($request, []);
//
//        $strTaskResult = $response->getBody()
//            ->getContents();
//        $cmd = new DeserializeTaskResult();
//        $taskResult = $cmd->__invoke($strTaskResult);
//
//        return $taskResult;
//    }

    final protected function requestTaskExecution(
        string $flowId,
        array  $arguments = [],
        string $projectEnvironmentId = null
    ): FlowRunResult {
        $executeLocal = false;

        $environment = $this->dependencyManager->get(Environment::class);
        $projectEnvironment = $environment->getProjectEnvironment();

        if (\is_null($projectEnvironmentId) || $projectEnvironmentId === $projectEnvironment->id) {
            $projectEnvironmentId = $projectEnvironment->id;
            $executeLocal = $projectEnvironment->isLocal;
        }

        if ($executeLocal) {
            return $this->executeLocal($flowId, $arguments, $projectEnvironmentId);
        } else {
            $result = $this->attlazClient->getFlowEndpoint()->requestRunFlow($flowId, $arguments, $projectEnvironmentId);
            return new FlowRunResult($flowId, $result->result, true);
        }
    }

    private function executeLocal(
        string $flowId,
        array  $arguments = [],
        string $projectEnvironmentId = null
    ): FlowRunResult {
        $executionId = $this->attlazClient->getFlowEndpoint()->createFlowRun($flowId, $projectEnvironmentId);

        $request = new FlowRunRequest($flowId, $arguments, $executionId);

        $commandManager = $this->dependencyManager->get(CommandManager::class);

        return $commandManager->runFlow($request);
    }

    /** @deprecated */
//    final  protected function sendTaskWithoutResult(Task $task, string $branch): void
//    {
//        $request = $this->createRequest($task, $branch);
//
//        //echo $request->getBody() . \PHP_EOL;
//        $this->getHTTPClient()
//            ->send($request);
//    }
    //
    //    protected final function executeMultiple(array $tasks): array
    //    {
    //        $results = [];
    //        foreach ($tasks as $task) {
    //            if ($task instanceof Task) {
    //                $results[] = $this->execute($task);
    //            }
    //        }
    //
    //        return $results;
    //    }
    //
    //    protected final function executeAsync(Task $task): PromiseInterface
    //    {
    //
    //        return $this->manager->executeAsync($task);
    ////        $deferred = new \React\Promise\Deferred();
    ////
    ////        $result = $this->execute($task);
    ////        $deferred->resolve($result);
    //
    //        // Execute a Node.js-style function using the callback pattern
    ////        computeAwesomeResultAsynchronously(function ($error, $result) use ($deferred) {
    ////            if ($error) {
    ////                $deferred->reject($error);
    ////            } else {
    ////                $deferred->resolve($result);
    ////            }
    ////        });
    //
    //        // Return the promise
    //        //  return $deferred->promise();
    //    }
    //
    /** @deprecated */
//    private function getHTTPClient(): HttpClient
//    {
//        if (\is_null($this->client)) {
//            //  $handler = HandlerStack::create($this->getMultiHandler());
//            $this->client = new HttpClient([
//                //  'headers' => [],
//                //   'handler' => HandlerStack::create($handler),
//                //                'connect_timeout' => 5,
//                //                'read_timeout'    => 5,
//                //                'timeout'         => 5,
//            ]);
//        }
//
//        return $this->client;
//    }

    //    private $curlMultiHandler;
    //
    //    private function getMultiHandler()
    //    {
    //        if (\is_null($this->curlMultiHandler)) {
    //            $this->curlMultiHandler = new CurlMultiHandler;
    //        }
    //
    //        return $this->curlMultiHandler;
    //    }
    /** @deprecated */
//    final  protected function executeMultipleAsync(TaskCollection $tasks, string $branch): TaskExecutionResultCollection
//    {
//        $results = new TaskExecutionResultCollection();
//
//        echo 'Create promises' . \PHP_EOL;
//        $promises = (function () use ($tasks, $branch) {
//            foreach ($tasks as $task) {
//                $request = $this->createRequest($task, $branch, true);
//
//                yield $this->getHTTPClient()
//                    ->sendAsync($request)
//                    ->then(function (ResponseInterface $response) use ($task) {
//                        // echo $task->getArgument('input') . \PHP_EOL;
//                        $strTaskResult = $response->getBody()
//                            ->getContents();
//                        //
//                        //                               $cmd = new DeserializeTaskResult();
//                        //                               $taskResult = $cmd->__invoke($strTaskResult);
//                        //
//                        return [
//                            'task' => $task,
//                            'result' => $strTaskResult,
//                        ];
//
//                        return true;
//                    }, function (\Exception $ex) use ($task) {
//                        //TODO: retry
//                        $strErrorMessage = 'Unable to execute task: ' . $task->name . ': ' . $ex->getMessage();
//                        $this->logger->error($strErrorMessage);
//
//                        return false;
//                    });
//            }
//        })();
//        echo 'Start sending' . \PHP_EOL;
//        //https://blog.madewithlove.be/post/concurrent-http-requests/
//        $each = new EachPromise($promises, [
//            'concurrency' => 5,
//            //            'fulfilled'   => function ($value, $idx, Promise $aggregat) use (&$results) {
//            //                echo 'Done' . \PHP_EOL;
//            //                //$results->addTaskResult($value['result']);
//            //            },
//            //            'rejected'    => function (\Exception $reason, $idx, Promise $aggregat) use (&$results) {
//            //                // echo \get_class($reason) . \PHP_EOL;
//            //                echo 'Ex: ' . $reason->getMessage() . \PHP_EOL;
//            //            },
//        ]);
//
//        $each->promise()
//            ->wait();
//
//        //        echo 'State: ' . $each->promise()
//        //                              ->getState() . \PHP_EOL;
//        //        while ($each->promise()
//        //                    ->getState() === 'pending') {
//        //            $this->getMultiHandler()
//        //                 ->tick();
//        //        }
//
//        return $results;
//    }

    /** @deprecated */
//    private function createRequest(Task $task, string $branch, bool $await = false): Request
//    {
//        $endPoint = 'http://hq.attlaz.com:14810/task/execute';
//        //$endPoint = 'https://www.google.com';
//        //TODO: get endpoint from configuration
//        $uri = $endPoint . '?branch=' . $branch;
//        if ($await) {
//            $uri = $endPoint . '?branch=' . $branch . '&wait=1';
//        }
//        $headers = [
//            'Content-Type' => 'application/json',
//        ];
//
//        $body = $task->__toString();
//
//        $request = new Request('POST', $uri, $headers, $body);
//
//        return $request;
//    }
}
