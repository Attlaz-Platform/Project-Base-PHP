<?php
declare(strict_types=1);

namespace Attlaz\Core\Model;

use Attlaz\Core\Command\DeserializeTaskFromString;
use Attlaz\Core\Command\ExecuteTask;
use Attlaz\Core\Command\SerializeTaskResult;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Worker
{

    /**
     * Listens for incoming messages
     */
    public function listen(): void
    {
        $connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        $channel->queue_declare('rpc_queue', false, false, false, false);

        $channel->basic_qos(null, 1, null);

        $channel->basic_consume('rpc_queue', '', false, false, false, false, [
            $this,
            'callback',
        ]);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    /**
     * Executes when a message is received.
     *
     * @param AMQPMessage $req
     */
    public function callback(AMQPMessage $req): void
    {

        try {
            $task = $this->decodeBodyToTask($req->body);
            $taskResult = $this->executeTask($task);
        } catch (\Throwable $ex) {
            $taskResult = $this->getErrorTaskResult($ex);
        }
        $cmd = new SerializeTaskResult();
        $serializedTaskResult = $cmd->__invoke($taskResult);

        /*
         * Creating a reply message with the same correlation id than the incoming message
         */
        $msg = new AMQPMessage($serializedTaskResult, ['correlation_id' => $req->get('correlation_id')]);

        /*
         * Publishing to the same channel from the incoming message
         */
        $req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));

        /*
         * Acknowledging the message
         */
        $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
    }

    private function getErrorTaskResult(\Throwable $error): TaskResult
    {
        return new TaskResult(null, 'Unable to execute task: ' . $error->getMessage(), false);
    }

    private function decodeBodyToTask(string $body): Task
    {
        return (new DeserializeTaskFromString)($body);
    }

    private function executeTask(Task $task): TaskResult
    {
        $cmd = new ExecuteTask();

        return $cmd->__invoke($task);

    }
}