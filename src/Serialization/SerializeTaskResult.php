<?php
declare(strict_types=1);

namespace Attlaz\Project\Serialization;

use Attlaz\Project\Model\TaskExecutionResult;

class SerializeTaskResult
{
    public function __invoke(TaskExecutionResult $taskResult): string
    {
        $serialized = json_encode($taskResult);

        return $serialized;
    }
}
