<?php
declare(strict_types=1);

namespace Attlaz\Core\Model\Manager;

use Attlaz\Core\Model\Settings;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Psr\Log\LoggerInterface;

abstract class Manager
{
    /** @var Settings */
    protected $settings;
    /** @var LoggerInterface */
    protected $logger;
    /** @var  AMQPChannel */
    protected $channel;
    /** @var  AMQPStreamConnection */
    protected $connection;

    public function __construct(Settings $settings, LoggerInterface $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    protected function initChannel()
    {
        $this->connection = new AMQPStreamConnection($this->settings->queue_job_host, $this->settings->queue_job_port, $this->settings->queue_job_user, $this->settings->queue_job_password);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($this->settings->queue_job_name, false, true, false, false);
    }

    protected function closeChannel()
    {
        $this->channel->close();
        $this->connection->close();
    }
}