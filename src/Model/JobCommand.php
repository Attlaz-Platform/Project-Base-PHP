<?php
declare(strict_types=1);

namespace Attlaz\Core\Model;

use Attlaz\Core\Model\Manager\RemoteManager;
use GuzzleHttp\Promise\PromiseInterface;
use React\Promise\Promise;

class JobCommand
{
    private $manager;

    public function __construct()
    {
        $this->manager = new RemoteManager();
    }

    protected final function execute(Task $task): TaskResult
    {

        return $this->manager->execute($task);

//        $logger = new \Monolog\Logger('Attlaz');
//        $logger->pushHandler(new \Monolog\Handler\StreamHandler(STDOUT));
//
//        $ex = new \Attlaz\Core\Command\ExecuteTask();
//
//        return $ex->__invoke($task, $logger);
    }

    protected final function executeMultiple(array $tasks): array
    {
        $results = [];
        foreach ($tasks as $task) {
            if ($task instanceof Task) {
                $results[] = $this->execute($task);
            }
        }

        return $results;
    }

    protected final function executeAsync(Task $task): PromiseInterface
    {

        return $this->manager->executeAsync($task);
//        $deferred = new \React\Promise\Deferred();
//
//        $result = $this->execute($task);
//        $deferred->resolve($result);

        // Execute a Node.js-style function using the callback pattern
//        computeAwesomeResultAsynchronously(function ($error, $result) use ($deferred) {
//            if ($error) {
//                $deferred->reject($error);
//            } else {
//                $deferred->resolve($result);
//            }
//        });

        // Return the promise
        //  return $deferred->promise();
    }

    protected final function executeMultipleAsync(array $tasks): PromiseInterface
    {

//        $deferred = new \React\Promise\Deferred();
//
//        $result = $this->executeMultiple($tasks);
//        $deferred->resolve($result);
//
//        // Return the promise
//        return $deferred->promise();
    }
}