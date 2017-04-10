<?php
declare(strict_types=1);

namespace Attlaz\Core\Command;

use Attlaz\Core\Model\Task;
use Attlaz\Core\Model\TaskResult;

class ExecuteTask
{
    public function __invoke(Task $task): TaskResult
    {
        switch ($task->getMethod()) {
            case 'dummy':

                $message = $task->getArguments()['input'];

                return new TaskResult($task, 'I received message "' . $message . '" and responded', true);
                break;

            default:
                return new TaskResult($task, 'Unknown task method "' . $task->getMethod() . '"', false);

        }

    }
}