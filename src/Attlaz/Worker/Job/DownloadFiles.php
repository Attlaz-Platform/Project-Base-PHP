<?php
declare(strict_types=1);

namespace Attlaz\Worker\Job;

use Attlaz\Framework\Model\TaskResult;
use Attlaz\Worker\Model\JobCommand;

class DownloadFiles extends JobCommand
{
    public function __invoke(array $urls, bool $async): array
    {
        $results = [];

        if ($async) {
            $tasks = [];
            foreach ($urls as $url) {
                $tasks[] = new \Attlaz\Framework\Model\Task('download_file', ['url' => $url]);
            }

            $taskResults = $this->executeMultipleAsync($tasks);

            /** @var TaskResult $taskResult */
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
        $task = new \Attlaz\Framework\Model\Task('download_file', ['url' => $url]);

        $taskResult = $this->sendTaskWithResult($task);

        return (string)$taskResult->getData();
    }
}