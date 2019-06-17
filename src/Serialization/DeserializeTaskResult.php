<?php
declare(strict_types=1);

namespace Attlaz\Project\Serialization;

use Attlaz\Project\Model\Task;
use Attlaz\Project\Model\TaskExecutionResult;

class DeserializeTaskResult
{
    public function __invoke(string $serializedTaskResult): TaskExecutionResult
    {
        $taskObject = json_decode($serializedTaskResult, true);
        if (\is_null($taskObject)) {
            throw new \Exception('Unable to deserialize task result, decoded object cannot be null');
        }

        if (!\key_exists('task', $taskObject)) {
            $strErrorMessage = 'Unable to deserialize task result, ';
            $strErrorMessage .= 'task is not defined [' . $serializedTaskResult . ']';
            throw new \InvalidArgumentException($strErrorMessage);
        }
        if (!\key_exists('data', $taskObject)) {
            $strErrorMessage = 'Unable to deserialize task result, ';
            $strErrorMessage .= 'data is not defined [' . $serializedTaskResult . ']';
            throw new \InvalidArgumentException($strErrorMessage);
        }
        if (!\key_exists('success', $taskObject)) {
            $strErrorMessage = 'Unable to deserialize task result, ';
            $strErrorMessage .= 'success is not defined [' . $serializedTaskResult . ']';
            throw new \InvalidArgumentException($strErrorMessage);
        }

        $taskArray = $taskObject['task'];
        if (!is_array($taskArray)) {
            $strErrorMessage = 'Unable to deserialize task result, ';
            $strErrorMessage .= 'property task must be serialized as array [' . $serializedTaskResult . ']';
            throw new \InvalidArgumentException($strErrorMessage);
        }

        $task = Task::fromArray($taskArray);

        $data = $taskObject['data'];

        $success = $taskObject['success'];
        if (!is_bool($success)) {
            $strErrorMessage = 'Unable to deserialize task result, ';
            $strErrorMessage .= 'property success must be serialized as bool [' . $serializedTaskResult . ']';
            throw new \InvalidArgumentException($strErrorMessage);
        }
        $task = new TaskExecutionResult($task->id, $data, $success);

        //TODO: change received, responded to task history objects
        //        $received = $taskObject['received'];
        //
        //        if (!is_array($received)) {
        //            throw new \InvalidArgumentException('Unable to deserialize task result,
        // property received must be serialized as array [' . $serializedTaskResult . ']');
        //        }
        //        $received = DateTimeHelper::deserialize($received);
        //        $task->setReceived($received);
        //
        ////
        //        $responded = $taskObject['responded'];
        //        if (!is_array($responded)) {
        //            throw new \InvalidArgumentException('Unable to deserialize task result,
        // property responded must be serialized as array [' . $serializedTaskResult . ']');
        //        }
        //        $responded = DateTimeHelper::deserialize($responded);
        //        $task->setResponded($responded);

        return $task;
    }
}
