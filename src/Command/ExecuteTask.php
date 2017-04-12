<?php
declare(strict_types=1);

namespace Attlaz\Core\Command;

use Attlaz\Core\Command\Job\DownloadFile;
use Attlaz\Core\Model\Task;
use Attlaz\Core\Model\TaskResult;
use Psr\Log\LoggerInterface;

class ExecuteTask
{
    public function __invoke(Task $task, LoggerInterface $logger = null): TaskResult
    {
        if ($logger) {
            $logger->debug('Execute task: ' . $task->getMethod());
        }
        switch ($task->getMethod()) {
            case 'dummy':

                $message = $task->getArguments()['input'];
                sleep(5);

                return new TaskResult($task, 'I received message "' . $message . '" and responded', true);
                break;
            case 'log':
                if ($logger) {
                    $message = $task->getArguments()['input'];

                    echo json_encode($message, JSON_PRETTY_PRINT);
                    //$logger->debug('Log: ' . $message);
                }

                return new TaskResult($task, '', true);
                break;
            case 'download':

                $url = $task->getArguments()['url'];

                $cmd = new DownloadFile();
                $content = $cmd->__invoke($url);

                $res = new TaskResult($task, $content, true);

                return $res;

                break;
            default:
                return new TaskResult($task, 'Unknown task method "' . $task->getMethod() . '"', false);

        }

    }
}