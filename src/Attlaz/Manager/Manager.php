<?php
declare(strict_types=1);

namespace Attlaz\Manager;

use Attlaz\Queue\Model\Settings;
use Attlaz\Queue\Queue;
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

    private $queueConnection;

    public function __construct(Settings $settings, LoggerInterface $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;

        $this->queueConnection = new Queue($settings, $logger);
    }

    protected function initChannel()
    {
        $this->queueConnection->connect();
        $this->channel = $this->queueConnection->getChannel();
    }

    protected function closeChannel()
    {
        $this->queueConnection->disconnect();
    }
}