<?php
declare(strict_types=1);

namespace Attlaz\Framework\Model;

use Echron\DataTypes\BasicCollection;

class TaskResultCollection extends BasicCollection
{

    public function addTaskResult(TaskResult $taskResult)
    {
        parent::addToCollection($taskResult);
    }

    public function current(): TaskResult
    {
        return parent::current();
    }

}