<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

use Echron\DataTypes\BasicCollection;

class TaskExecutionResultCollection extends BasicCollection
{

    public function addTaskResult(TaskExecutionResult $taskResult)
    {
        parent::addToCollection($taskResult);
    }

    public function current(): TaskExecutionResult
    {
        return parent::current();
    }

}