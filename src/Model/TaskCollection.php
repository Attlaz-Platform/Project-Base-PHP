<?php
declare(strict_types=1);

namespace Attlaz\Project\Model;

use Echron\DataTypes\BasicCollection;

class TaskCollection extends BasicCollection
{

    public function addTask(Task $taskResult): int
    {
        return parent::addToCollection($taskResult);
    }
}
