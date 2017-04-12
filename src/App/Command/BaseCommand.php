<?php
declare(strict_types=1);

namespace Attlaz\Core\App\Command;

use Attlaz\Core\Model\Settings;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BaseCommand extends Command implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    protected function getExchange(): string
    {
        return $this->exchange;
    }

    protected function getQueueName(): string
    {
        return $this->queue;
    }

    /** @var  AMQPStreamConnection */
    protected $connection;

    private $exchange = 'router';
    private $queue = 'hello';

    private $channel;

    protected function initChannel(Settings $settings)
    {

        $this->connection = new AMQPStreamConnection($settings->queue_job_host, $settings->queue_job_port, $settings->queue_job_user, $settings->queue_job_password);
        $this->channel = $this->connection->channel();
        $this->channel->queue_declare($settings->queue_job_name, false, true, false, false);


    }

    protected function getChannel(): AMQPChannel
    {
        if (!$this->channel) {
            $this->initChannel();
        }

        return $this->channel;
    }

    protected function stop()
    {

        try {
            $this->getChannel()
                 ->close();
            $this->connection->close();

        } catch (\Exception $ex) {

        }
        unset($this->channel);
    }
}