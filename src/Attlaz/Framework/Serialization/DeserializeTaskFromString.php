<?php
declare(strict_types=1);

namespace Attlaz\Framework\Serialization;

use Attlaz\Framework\Model\Task;

class DeserializeTaskFromString
{
    public function __invoke(string $serializedTask): Task
    {
        $taskArray = json_decode($serializedTask, true);

        $cmd = new DeserializeTaskFromArray();

        return $cmd->__invoke($taskArray);

    }
}