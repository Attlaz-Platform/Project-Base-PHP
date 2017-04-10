<?php
declare(strict_types=1);

namespace Attlaz\Core\Model;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Manager
{
    private $response;

    /**
     * @var string
     */
    private $corr_id;

    /**
     * @param string $message
     * @return string
     */
    public function execute(string $message)
    {
        $connection = new AMQPStreamConnection('rabbit', 5672, 'guest', 'guest');
        $channel = $connection->channel();

        /*
         * creates an anonymous exclusive callback queue
         * $callback_queue has a value like amq.gen-_U0kJVm8helFzQk9P0z9gg
         */
        list($callback_queue, ,) = $channel->queue_declare("", false, false, true, false);

        $channel->basic_consume($callback_queue, '', false, false, false, false, [
            $this,
            'onResponse',
        ]);

        $this->response = null;

        /*
         * $this->corr_id has a value like 53e26b393313a
         */
        $this->corr_id = uniqid();
        $jsonCredentials = json_encode($message);

        /*
         * create a message with two properties: reply_to, which is set to the
         * callback queue and correlation_id, which is set to a unique value for
         * every request
         */
        $msg = new AMQPMessage($jsonCredentials, [
            'correlation_id' => $this->corr_id,
            'reply_to'       => $callback_queue,
        ]);

        /*
         * The request is sent to an rpc_queue queue.
         */
        $channel->basic_publish($msg, '', 'rpc_queue');

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
            $this->response = $rep->body;
        }
    }
}