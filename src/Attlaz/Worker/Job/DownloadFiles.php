<?php
declare(strict_types=1);

namespace Attlaz\Worker\Job;

use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskCollection;
use Attlaz\Worker\Model\JobCommand;

class DownloadFiles extends JobCommand
{
    public function __invoke(array $urls, bool $async): array
    {
        $results = [];

        if ($async) {
            $tasks = new TaskCollection();
            foreach ($urls as $url) {
                $tasks->addTask(new Task('download_file', ['url' => $url]));
            }

            $taskResults = $this->executeMultipleAsync($tasks);

            foreach ($taskResults as $taskResult) {
                $results[] = $taskResult->getData();
            }

        } else {
            foreach ($urls as $url) {
                $results[] = $this->downloadFile($url);
            }
        }

        return $results;

    }

    private function downloadFile(string $url): string
    {
        $task = new Task('download_file', ['url' => $url]);

        $taskResult = $this->sendTaskWithResult($task);

        return (string)$taskResult->getData();
    }
}