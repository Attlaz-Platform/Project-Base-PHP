<?php
declare(strict_types=1);

namespace Attlaz\Worker\Model;

use Attlaz\Framework\App\Logger;
use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Framework\Serialization\DeserializeTaskResult;
use Attlaz\Framework\Serialization\SerializeTask;
use Attlaz\Manager\Manager;

use Attlaz\Framework\Model\Settings;
use GuzzleHttp\Psr7\Request;

class JobCommand
{
    private $manager;

    public function __construct(Settings $settings, Logger $logger)
    {
        $this->manager = new Manager($settings, $logger);
    }

    protected final function sendTaskWithResult(Task $task): TaskResult
    {
        return $this->manager->sendTaskWithResult($task);
    }

    protected final function sendTaskWithoutResult(Task $task): void
    {
        $this->manager->sendTaskWithoutResult($task);
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
    protected final function executeMultipleAsync(array $tasks): array
    {

        $results = [];
        $client = new \GuzzleHttp\Client([]);
        $promises = (function () use ($tasks, $client) {

            foreach ($tasks as $task) {

                $request = $this->createRequest($task);

                yield $client->sendAsync($request)
                             ->then(function (\Psr\Http\Message\ResponseInterface $response) use ($task) {


//                                 echo 'Response: ' . $response->getBody()
//                                                              ->getContents();

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

        $each = new \GuzzleHttp\Promise\EachPromise($promises, [
            'concurrency' => 4,
            'fulfilled'   => function (array $response) use (&$results) {

                //echo 'Fulfilled response: ' . $response['result'] . \PHP_EOL;
//        if ($response instanceof GuzzleHttp\Psr7\Response) {
//
//        }
//
//        echo get_class($response) . PHP_EOL;
//        echo $response->getBody()
//                      ->getContents();
//        $profile = json_decode($response->getBody()
//                                        ->getContents(), true);

                $results[] = $response['result'];
                //  echo 'Finished' . PHP_EOL;
                // Do something with the profile.
            },
        ]);

        $each->promise()
             ->wait();

        return $results;
    }

    private function createRequest(Task $task): Request
    {
        $uri = 'http://api:80/execute.php';
        $headers = [];

        $cmd = new SerializeTask();
        $body = $cmd->__invoke($task);

        $request = new Request('POST', $uri, $headers, $body);

        return $request;
    }
}