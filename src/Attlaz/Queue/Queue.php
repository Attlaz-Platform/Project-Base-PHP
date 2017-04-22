<?php
declare(strict_types=1);

namespace Attlaz\Queue;

use Attlaz\Queue\Exception\UnableToConnectToQueueException;
use Attlaz\Framework\Model\Settings;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class Queue
{
    private $settings;
    /** @var  AMQPStreamConnection */
    private $connection;
    /** @var  AMQPChannel */
    private $channel;
    private $logger;

    public function __construct(Settings $settings, LoggerInterface $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    public function connect()
    {


        $tryConnecting = true;
        $retryAfterSeconds = 5;

        $retries = 0;
        $maxRetries = 3;
        while ($tryConnecting) {
            $retries++;
            $tryConnecting = false;
            try {
                $this->connection = $this->createConnection();

            } catch (\Exception $ex) {
                $this->logger->error($ex->getMessage());
                if ($retries < $maxRetries) {
                    $tryConnecting = true;
                    $this->logger->debug('Retry connection in ' . $retryAfterSeconds . ' seconds');
                    sleep($retryAfterSeconds);
                } else {
                    throw $ex;
                }

            }
        }

        $this->channel = $this->connection->channel();

        //Open channel
        $this->channel->queue_declare($this->settings->queue_job_name, false, true, false, false);
    }

    public function disconnect()
    {
        $this->channel->close();
        $this->connection->close();
    }

    public function getChannel(): AMQPChannel
    {
        return $this->channel;
    }

    private function createConnection(): AMQPStreamConnection
    {

        //http://www.rabbitmq.com/heartbeats.html
        $heartbeat = 15;

        try {
            $host = $this->settings->queue_job_host;
            $port = $this->settings->queue_job_port;
            $user = $this->settings->queue_job_user;
            $password = $this->settings->queue_job_password;
            $vhost = '/';
            $insist = false;
            $login_method = 'AMQPLAIN';
            $login_response = null;
            $locale = 'en_US';
            $connection_timeout = 3.0;

            if ($heartbeat === 0) {
                //Default value
                $read_write_timeout = 3.0;
            } else {
                $read_write_timeout = 2 * $heartbeat;
            }

            $context = null;
            $keepalive = true;

            $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost, $insist, $login_method, $login_response, $locale, $connection_timeout, $read_write_timeout, $context, $keepalive, $heartbeat);

            return $connection;
        } catch (\Exception $ex) {
            throw new UnableToConnectToQueueException('Unable to connect to queue "' . $this->settings->queue_job_host . ':' . $this->settings->queue_job_port . '": ' . $ex->getMessage() . '', 0, $ex);
        }

    }

    public function publishMessage(AMQPMessage $message, string $queueName)
    {
        $this->logger->debug('Send message to queue', ['queue' => $queueName]);
        $this->channel->basic_publish($message, '', $queueName);
    }
}