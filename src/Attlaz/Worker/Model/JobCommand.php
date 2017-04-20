<?php
declare(strict_types=1);

namespace Attlaz\Worker\Model;

use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskCollection;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Framework\Model\TaskResultCollection;
use Attlaz\Framework\Serialization\DeserializeTaskResult;
use Attlaz\Framework\Serialization\SerializeTask;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\EachPromise;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\ResponseInterface;

class JobCommand
{

    private $client;

    public function __construct()
    {

        $this->client = new Client([]);
    }

    protected final function sendTaskWithResult(Task $task): TaskResult
    {
        $request = $this->createRequest($task);
        /** @var ResponseInterface $response */
        $response = $this->client->send($request, true);

        $strTaskResult = $response->getBody()
                                  ->getContents();
        $cmd = new DeserializeTaskResult();
        $taskResult = $cmd->__invoke($strTaskResult);

        return $taskResult;
    }

    protected final function sendTaskWithoutResult(Task $task): void
    {
        $request = $this->createRequest($task);
        $this->client->send($request);
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
    protected final function executeMultipleAsync(TaskCollection $tasks): TaskResultCollection
    {

        $results = new TaskResultCollection();

        $promises = (function () use ($tasks) {


            foreach ($tasks as $task) {

                $request = $this->createRequest($task, true);

                yield $this->client->sendAsync($request)
                                   ->then(function (ResponseInterface $response) use ($task) {

                                       $strTaskResult = $response->getBody()
                                                                 ->getContents();

                                       $cmd = new DeserializeTaskResult();
                                       $taskResult = $cmd->__invoke($strTaskResult);

                                       return [
                                           'task'   => $task,
                                           'result' => $taskResult,
                                       ];
                                   });
            }
        })();

        //https://blog.madewithlove.be/post/concurrent-http-requests/
        $each = new EachPromise($promises, [
            'concurrency' => 100,
            'fulfilled'   => function (array $response) use (&$results) {

                $results->addTaskResult($response['result']);
            },
        ]);

        $each->promise()
             ->wait();

        return $results;
    }

    private function createRequest(Task $task, bool $await = false): Request
    {
        $uri = 'http://api:80/execute.php';
        if ($await) {
            $uri = 'http://api:80/execute.php?wait=1';
        }
        $headers = [];

        $cmd = new SerializeTask();
        $body = $cmd->__invoke($task);

        $request = new Request('POST', $uri, $headers, $body);

        return $request;
    }
}