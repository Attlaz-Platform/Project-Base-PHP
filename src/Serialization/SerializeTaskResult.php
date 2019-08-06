<?php
declare(strict_types=1);

namespace Attlaz\Project\Serialization;

use Attlaz\Project\Model\TaskExecutionResult;

class SerializeTaskResult
{
    public function __invoke(TaskExecutionResult $taskResult): string
    {
        $serialized = json_encode($taskResult);

        if (!\is_string($serialized)) {
            throw new \Exception('Unable to encode task result');
        }

        return $serialized;
    }
}
