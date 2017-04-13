<?php
declare(strict_types=1);

namespace Attlaz\Queue\Model\Manager;


use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Serialization\SerializeTask;
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

        $this->logger->debug('Send message [queue: ' . $this->settings->queue_job_name . ']');
        $this->channel->basic_publish($msg, '', $this->settings->queue_job_name);
    }
}