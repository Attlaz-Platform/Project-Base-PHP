<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

use Echron\DataTypes\BasicCollection;

class TaskCollection extends BasicCollection
{

    public function addTask(Task $taskResult)
    {
        parent::addToCollection($taskResult);
    }

    public function current(): Task
    {
        return parent::current();
    }

}