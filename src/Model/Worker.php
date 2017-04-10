<?php
declare(strict_types=1);

namespace Attlaz\Core\Model;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class Worker
{

    /**
     * Listens for incoming messages
     */
    public function listen()
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
    public function callback(AMQPMessage $req)
    {

        $credentials = json_decode($req->body);
        $authResult = $this->auth($credentials);

        /*
         * Creating a reply message with the same correlation id than the incoming message
         */
        $msg = new AMQPMessage(json_encode(['status' => $authResult]), ['correlation_id' => $req->get('correlation_id')]);

        /*
         * Publishing to the same channel from the incoming message
         */
        $req->delivery_info['channel']->basic_publish($msg, '', $req->get('reply_to'));

        /*
         * Acknowledging the message
         */
        $req->delivery_info['channel']->basic_ack($req->delivery_info['delivery_tag']);
    }

    private function auth(string $message): string
    {
        return $message . ' RECIEVED';
//        if (($credentials->username == 'admin') && ($credentials->password == 'admin')) {
//            return true;
//        } else {
//            return false;
//        }
    }
}