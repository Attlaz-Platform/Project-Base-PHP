<?php
declare(strict_types=1);

namespace Attlaz\Framework\Serialization;

use Attlaz\Framework\Model\Task;

class DeserializeTaskFromArray
{
    public function __invoke(array $taskArray): Task
    {

        if (!isset($taskArray['method']) || !isset($taskArray['arguments'])) {
            throw new \InvalidArgumentException('Unable to deserialize task, properties method and arguments are required');
        }

        $method = $taskArray['method'];
        $arguments = $taskArray['arguments'];

        $task = new Task($method, $arguments);

        return $task;

    }
}