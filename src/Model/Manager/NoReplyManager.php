<?php
declare(strict_types=1);

namespace Attlaz\Core\Model\Manager;

use Attlaz\Core\Command\SerializeTask;
use Attlaz\Core\Model\Task;
use PhpAmqpLib\Message\AMQPMessage;

class NoReplyManager extends Manager
{

    private $correlation_id;

    /**
     * @param Task $task
     */
    public function execute(Task $task): void
    {
        $this->initChannel();

        $this->sendTaskToQueue($task);

        $this->closeChannel();

    }

    /**
     * @param $task
     */
    private function sendTaskToQueue(Task $task): void
    {
        $cmd = new SerializeTask();
        $jsonTask = $cmd->__invoke($task);

        $msg = new AMQPMessage($jsonTask, [
            'correlation_id' => $this->correlation_id,
        ]);

        $this->logger->debug('Send message [queue: ' . $this->settings->queue_queue . ']');
        $this->channel->basic_publish($msg, '', $this->settings->queue_queue);
    }
}