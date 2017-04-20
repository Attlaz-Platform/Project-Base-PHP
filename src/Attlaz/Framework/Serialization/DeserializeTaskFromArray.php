<?php
declare(strict_types=1);

namespace Attlaz\Framework\Serialization;

use Attlaz\Framework\Model\Task;

class DeserializeTaskFromArray
{
    public function __invoke(array $taskArray): Task
    {


        if (!\key_exists('arguments', $taskArray)) {
            throw new \InvalidArgumentException('Unable to deserialize task, arguments is not defined');
        }
        if (!\key_exists('method', $taskArray)) {
            throw new \InvalidArgumentException('Unable to deserialize task, method is not defined');
        }
        $method = $taskArray['method'];
        $arguments = $taskArray['arguments'];

        $task = new Task($method, $arguments);

        return $task;

    }
}