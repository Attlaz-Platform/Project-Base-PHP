<?php

namespace Attlaz\Queue\Controller;

use Attlaz\Queue\Model\Exception\UnableToConnectToQueueException;
use Attlaz\Queue\Model\Settings;
use Attlaz\Framework\Helper\DateTimeHelper;
use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Framework\Serialization\DeserializeTaskFromString;
use Attlaz\Framework\Serialization\SerializeTaskResult;

use Attlaz\Worker\Controller\ExecuteTask;
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
        $maxRetries = PHP_INT_MAX;
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

        try {
            $connection = new AMQPStreamConnection($this->settings->queue_job_host, $this->settings->queue_job_port, $this->settings->queue_job_user, $this->settings->queue_job_password);

            return $connection;
        } catch (\Exception $ex) {
            throw new UnableToConnectToQueueException('Unable to connect to queue (' . $this->settings->queue_job_host . ':' . $this->settings->queue_job_port . ') ', 0, $ex);
        }

    }
}