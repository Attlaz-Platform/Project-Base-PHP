<?php
declare(strict_types=1);

namespace Attlaz\Api\Command;

use Attlaz\Framework\Serialization\DeserializeTaskFromString;
use Attlaz\Framework\Serialization\SerializeTaskResult;

use Slim\Http\Request;
use Slim\Http\Response;

class TaskExecuteCommand extends ApiCommand
{

    public function __invoke(Request $request, Response $response): Response
    {
        $response = $response->withHeader('Content-Type', 'application/json');

        $awaitResponse = $request->getQueryParam('wait');
        if (!is_null($awaitResponse)) {
            $awaitResponse = true;
        } else {
            $awaitResponse = false;
        }

        $strTask = $request->getBody()
                           ->__toString();
        $cmd = new DeserializeTaskFromString();
        $task = $cmd->__invoke($strTask);

        $manager = $this->getManager();
        if ($awaitResponse) {
            $taskResult = $manager->sendTaskWithResult($task);

            $cmd = new SerializeTaskResult();
            $serializedTaskResult = $cmd->__invoke($taskResult);
            $this->logger->warning('Result: ' . $serializedTaskResult);

            $response->getBody()
                     ->write($serializedTaskResult);
        } else {
            $manager->sendTaskWithoutResult($task);

        }

        return $response;
    }

}