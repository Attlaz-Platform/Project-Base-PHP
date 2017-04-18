<?php
declare(strict_types=1);

namespace Attlaz\Worker\Job;

use Attlaz\Framework\Model\Task;
use Attlaz\Worker\Model\JobCommand;

class Example extends JobCommand
{

    public function __invoke()
    {
        $this->executeSubTask();
//        $this->executeSubTaskAsync();
//        $this->executeMultipleSubTasks();
    }

    private function executeSubTask()
    {
        $task = new Task('log', ['message' => 'Example started']);

        $this->execute($task);

    }

    private function executeSubTaskAsync()
    {
        $task = new Task('wait', ['seconds' => 1]);

        $this->executeAsync($task)
             ->then(function ($response) {
                 echo 'Task complete' . PHP_EOL;
             });

    }

//

    public function executeMultipleSubTasks()
    {
        $task1 = new Task('wait', ['seconds' => 1]);
        $task2 = new Task('wait', ['seconds' => 1]);
        $task3 = new Task('wait', ['seconds' => 1]);
        $task4 = new Task('wait', ['seconds' => 1]);
        $task5 = new Task('wait', ['seconds' => 1]);

        $results = $this->executeMultiple([
            $task1,
            $task2,
            $task3,
            $task4,
            $task5,
        ]);

    }

//    public function executeMultipleSubTasksAsync()
//    {
//        $task1 = new Task('wait', ['seconds' => 1]);
//        $task2 = new Task('wait', ['seconds' => 1]);
//        $task3 = new Task('wait', ['seconds' => 1]);
//        $task4 = new Task('wait', ['seconds' => 1]);
//        $task5 = new Task('wait', ['seconds' => 1]);
//
//        $results = $this->executeMultipleAsync([
//            $task1,
//            $task2,
//            $task3,
//            $task4,
//            $task5,
//        ])
//                        ->then(function ($response) {
//                            echo 'Tasks complete' . PHP_EOL;
//                        });
//    }

}