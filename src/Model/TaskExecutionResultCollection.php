<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

use Echron\DataTypes\BasicCollection;

class TaskExecutionResultCollection extends BasicCollection
{

    public function addTaskResult(TaskExecutionResult $taskResult): int
    {
        return parent::addToCollection($taskResult);
    }
}
