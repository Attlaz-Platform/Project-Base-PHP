<?php
declare(strict_types=1);

namespace Attlaz\Queue\Model\Manager;

use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;

class RemoteManager
{

    public function execute(Task $task): TaskResult
    {

        $request = $this->createRequest($task);
        $client = new Client(['timeout' => 10]);

        $result = $client->send($request);

        return $result;
    }

    public function executeAsync(Task $task): PromiseInterface
    {
        $request = $this->createRequest($task);
        $client = new Client(['timeout' => 10]);

        $result = $client->sendAsync($request);

        return $result;
    }

    private function createRequest(Task $task): Request
    {
        $uri = 'http://localhost:8000/job/' . $task->getMethod() . '/execute';
        $headers = [];

        $body = [];
        $body['parameters'] = $task->getArguments();
        $body = json_encode($body);
        $request = new Request('POST', $uri, $headers, $body);

        return $request;
    }
}