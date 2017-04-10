<?php
declare(strict_types=1);

namespace Attlaz\Core\Model\Manager;

use Attlaz\Core\Command\DeserializeTaskResult;
use Attlaz\Core\Command\SerializeTask;

use Attlaz\Core\Model\Task;
use Attlaz\Core\Model\TaskResult;
use PhpAmqpLib\Message\AMQPMessage;

class ReplyManager extends Manager
{

    private $response;
    /** @var  string */
    private $correlation_id;

    /**
     * @param Task $task
     * @return TaskResult
     */
    public function execute(Task $task): TaskResult
    {
        $this->initChannel();

        $callback_queue = $this->listenToPrivateResponseQueue();

        $this->response = null;

        /*
         * $this->corr_id has a value like 53e26b393313a
         */
        $this->correlation_id = uniqid();

        $this->sendTaskToQueue($task, $callback_queue);

        while (!$this->response) {
            //TODO: set timeout
            $this->channel->wait();
        }

        $this->closeChannel();

        return $this->response;
    }

    /**
     * When a message appears, it checks the correlation_id property. If it
     * matches the value from the request it returns the response to the
     * application.
     *
     * @param AMQPMessage $rep
     */
    public function onResponse(AMQPMessage $rep)
    {
        $this->logger->debug('Incoming response');
        if ($rep->get('correlation_id') == $this->correlation_id) {


            $serializedTaskResult = $rep->body;
            $cmd = new DeserializeTaskResult();
            $taskResult = $cmd->__invoke($serializedTaskResult);

            $this->response = $taskResult;
        }
    }

    /**
     * Creates an anonymous exclusive callback queue
     * @return mixed
     */
    private function listenToPrivateResponseQueue()
    {
        list($callback_queue, ,) = $this->channel->queue_declare('', false, false, true, false);

        $this->channel->basic_consume($callback_queue, '', false, false, false, false, [
            $this,
            'onResponse',
        ]);

        return $callback_queue;
    }

    /**
     * @param $task
     * @param $callback_queue
     */
    private function sendTaskToQueue(Task $task, $callback_queue): void
    {
        $cmd = new SerializeTask();
        $jsonTask = $cmd->__invoke($task);

        /*
         * create a message with two properties: reply_to, which is set to the
         * callback queue and correlation_id, which is set to a unique value for
         * every request
         */

        $msg = new AMQPMessage($jsonTask, [
            'correlation_id' => $this->correlation_id,
            'reply_to'       => $callback_queue,
        ]);

        /*
         * The request is sent to an rpc_queue queue.
         */
        $this->logger->debug('Send message [queue: ' . $this->settings->queue_queue . ']');

        $this->channel->basic_publish($msg, '', $this->settings->queue_queue);
    }
}