<?php
declare(strict_types=1);

namespace Attlaz\Project\Command;

use Attlaz\Project\App\Environment;
use Attlaz\Project\Model\Task;
use Attlaz\Project\Model\TaskCollection;
use Attlaz\Project\Model\TaskExecutionRequest;
use Attlaz\Project\Model\TaskExecutionResult;
use Attlaz\Project\Model\TaskExecutionResultCollection;
use Attlaz\Project\Serialization\DeserializeTaskResult;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * show off @method
 *
 * @method execute()
 */
abstract class AbstractCommand
{

    /**
     * @var Client|null
     */
    private $client;

    private $attlazClient;

    const INVOKE_METHOD = 'execute';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Attlaz\Project\App\Environment
     */
    protected $config;
    /**
     * @var \Attlaz\Project\Cache\CacheManager
     */
    protected $cacheManager;

    /**
     * @var \DI\Container
     */
    protected $dependencyManager;

    protected $outputHelper;

    public function __construct(CommandContext $context)
    {
        $this->logger = $context->getLogger();
        $this->config = $context->getConfig();
        $this->cacheManager = $context->getCacheManager();
        $this->dependencyManager = $context->getDependencyManager();
        $this->outputHelper = $context->getOutputHelper();

        $this->attlazClient = $this->dependencyManager->get(\Attlaz\Client::class);
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

    /** @deprecated */
    final protected function sendTaskWithResult(Task $task, string $branch): TaskExecutionResult
    {
        $request = $this->createRequest($task, $branch);
        /** @var ResponseInterface $response */
        $response = $this->getHTTPClient()
                         ->send($request, []);

        $strTaskResult = $response->getBody()
                                  ->getContents();
        $cmd = new DeserializeTaskResult();
        $taskResult = $cmd->__invoke($strTaskResult);

        return $taskResult;
    }

    final protected function requestTaskExecution(string $taskId, array $arguments = null)
    {
        $environment = $this->dependencyManager->get(Environment::class);

        $projectEnvironment = $environment->getProjectEnvironment();
        $projectEnvironmentId = $projectEnvironment->id;
        if ($projectEnvironment->isLocal) {
            $executionId = $this->attlazClient->createTaskExecution($taskId, $projectEnvironmentId);

            $request = new TaskExecutionRequest($taskId, $arguments, $executionId);

            $commandManager = $this->dependencyManager->get(CommandManager::class);

            $commandManager->executeTask($request);
        } else {
            return $this->attlazClient->requestTaskExecution($taskId, $arguments, $projectEnvironmentId);
        }
    }

    /** @deprecated */
    final  protected function sendTaskWithoutResult(Task $task, string $branch): void
    {
        $request = $this->createRequest($task, $branch);

        //echo $request->getBody() . \PHP_EOL;
        $this->getHTTPClient()
             ->send($request);
    }
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
    private function getHTTPClient(): Client
    {
        if (\is_null($this->client)) {
            //  $handler = HandlerStack::create($this->getMultiHandler());
            $this->client = new Client([
                //  'headers' => [],
                //   'handler' => HandlerStack::create($handler),
                //                'connect_timeout' => 5,
                //                'read_timeout'    => 5,
                //                'timeout'         => 5,
            ]);
        }

        return $this->client;
    }

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
    final  protected function executeMultipleAsync(TaskCollection $tasks, string $branch): TaskExecutionResultCollection
    {
        $results = new TaskExecutionResultCollection();

        echo 'Create promises' . \PHP_EOL;
        $promises = (function () use ($tasks, $branch) {
            foreach ($tasks as $task) {
                $request = $this->createRequest($task, $branch, true);

                yield $this->getHTTPClient()
                           ->sendAsync($request)
                           ->then(function (ResponseInterface $response) use ($task) {
                               // echo $task->getArgument('input') . \PHP_EOL;
                               $strTaskResult = $response->getBody()
                                                         ->getContents();
                               //
                               //                               $cmd = new DeserializeTaskResult();
                               //                               $taskResult = $cmd->__invoke($strTaskResult);
                               //
                               return [
                                   'task'   => $task,
                                   'result' => $strTaskResult,
                               ];

                               return true;
                           }, function (\Exception $ex) use ($task) {
                               //TODO: retry
                               $strErrorMessage = 'Unable to execute task: ' . $task->name . ': ' . $ex->getMessage();
                               $this->logger->error($strErrorMessage);

                               return false;
                           });
            }
        })();
        echo 'Start sending' . \PHP_EOL;
        //https://blog.madewithlove.be/post/concurrent-http-requests/
        $each = new EachPromise($promises, [
            'concurrency' => 5,
//            'fulfilled'   => function ($value, $idx, Promise $aggregat) use (&$results) {
//                echo 'Done' . \PHP_EOL;
//                //$results->addTaskResult($value['result']);
//            },
//            'rejected'    => function (\Exception $reason, $idx, Promise $aggregat) use (&$results) {
//                // echo \get_class($reason) . \PHP_EOL;
//                echo 'Ex: ' . $reason->getMessage() . \PHP_EOL;
//            },
        ]);

        $each->promise()
             ->wait();

        //        echo 'State: ' . $each->promise()
        //                              ->getState() . \PHP_EOL;
        //        while ($each->promise()
        //                    ->getState() === 'pending') {
        //            $this->getMultiHandler()
        //                 ->tick();
        //        }

        return $results;
    }

    /** @deprecated */
    private function createRequest(Task $task, string $branch, bool $await = false): Request
    {
        $endPoint = 'http://hq.attlaz.com:14810/task/execute';
        //$endPoint = 'https://www.google.com';
        //TODO: get endpoint from configuration
        $uri = $endPoint . '?branch=' . $branch;
        if ($await) {
            $uri = $endPoint . '?branch=' . $branch . '&wait=1';
        }
        $headers = [
            'Content-Type' => 'application/json',
        ];

        $body = $task->__toString();

        $request = new Request('POST', $uri, $headers, $body);

        return $request;
    }
}
