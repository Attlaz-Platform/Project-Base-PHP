<?php

declare(strict_types=1);

namespace Attlaz\Project\TaskExecution;

use Attlaz\Project\Model\TaskExecutionRequest;

class FPM extends AbstractTaskHandler
{
    public function run(): int
    {
        $taskExecutionRequest = $this->getTaskExecutionRequest();

        return $this->execute($taskExecutionRequest);
    }

    private function getTaskExecutionRequest(): TaskExecutionRequest
    {
        $json = file_get_contents('php://input');
        if (!\is_string($json)) {
            throw new \Exception('Unable to get input content');
        } else {
            $values = json_decode($json, true);
            var_dump($json);
            var_dump($values);

            $taskId = $values['taskId'];
            $arguments = $values['arguments'];

            $executionId = $values['executionId'];

            return new TaskExecutionRequest($taskId, $arguments, $executionId);
        }
    }
}
