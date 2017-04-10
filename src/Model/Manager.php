<?php
declare(strict_types=1);

namespace Attlaz\Core\Model;

use Attlaz\Core\Command\DeserializeTaskResult;
use Attlaz\Core\Command\SerializeTask;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Manager
{
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    private $response;

    /**
     * @var string
     */
    private $corr_id;

    /**
     * @param Task $task
     * @return TaskResult
     */
    public function execute(Task $task): TaskResult
    {
        $connection = new AMQPStreamConnection($this->settings->queue_host, $this->settings->queue_port, $this->settings->queue_user, $this->settings->queue_password);
        $channel = $connection->channel();

        /*
         * creates an anonymous exclusive callback queue
         * $callback_queue has a value like amq.gen-_U0kJVm8helFzQk9P0z9gg
         */
        list($callback_queue, ,) = $channel->queue_declare('', false, false, true, false);

        $channel->basic_consume($callback_queue, '', false, false, false, false, [
            $this,
            'onResponse',
        ]);

        $this->response = null;

        /*
         * $this->corr_id has a value like 53e26b393313a
         */
        $this->corr_id = uniqid();

        $cmd = new SerializeTask();
        $jsonTask = $cmd->__invoke($task);

        /*
         * create a message with two properties: reply_to, which is set to the
         * callback queue and correlation_id, which is set to a unique value for
         * every request
         */
        $msg = new AMQPMessage($jsonTask, [
            'correlation_id' => $this->corr_id,
            'reply_to'       => $callback_queue,
        ]);

        /*
         * The request is sent to an rpc_queue queue.
         */
        $channel->basic_publish($msg, '', $this->settings->queue_channel);

        while (!$this->response) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();

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
        if ($rep->get('correlation_id') == $this->corr_id) {


            $serializedTaskResult = $rep->body;
            $cmd = new DeserializeTaskResult();
            $taskResult = $cmd->__invoke($serializedTaskResult);

            $this->response = $taskResult;
        }
    }
}