<?php
declare(strict_types=1);

namespace Attlaz\Manager;

use Attlaz\Framework\App\Logger;
use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Manager\Handler\NoReplyHandler;
use Attlaz\Manager\Handler\ReplyHandler;
use Attlaz\Framework\Model\Settings;
use Attlaz\Queue\Queue;

class Manager
{

    private $replyHandler;
    private $noReplyHandler;

    /** @var Settings */
    protected $settings;
    /** @var Logger */
    protected $logger;

    private $queueConnection;

    public function __construct(Settings $settings, Logger $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;

        $this->queueConnection = new Queue($settings, $logger);

        $this->replyHandler = new ReplyHandler($logger);
        $this->noReplyHandler = new NoReplyHandler($logger);

    }

    public function sendTaskWithoutResult(Task $task): void
    {
        $this->queueConnection->connect();

        $this->noReplyHandler->setQueue($this->queueConnection);
        $this->noReplyHandler->execute($task);

        $this->queueConnection->disconnect();
    }

    public function sendTaskWithResult(Task $task): TaskResult
    {
        $this->queueConnection->connect();

        $this->replyHandler->setQueue($this->queueConnection);
        $response = $this->replyHandler->execute($task);

        $this->queueConnection->disconnect();

        return $response;
    }
}