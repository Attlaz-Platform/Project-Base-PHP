<?php
declare(strict_types=1);

namespace Attlaz\Manager\Handler;

use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Serialization\SerializeTask;
use PhpAmqpLib\Message\AMQPMessage;

class NoReplyHandler extends Handler
{

    private $correlation_id;

    public function execute(Task $task): void
    {

        //TODO: what is this correlation_id?
        $cmd = new SerializeTask();
        $jsonTask = $cmd->__invoke($task);

        $message = new AMQPMessage($jsonTask, [
            'correlation_id' => $this->correlation_id,
        ]);

        $this->queue->publishMessage($message, 'task');

    }
}