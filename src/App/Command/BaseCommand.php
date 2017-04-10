<?php
declare(strict_types=1);

namespace Attlaz\Core\App\Command;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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

    protected function initChannel()
    {
        $this->connection = new AMQPStreamConnection('rabbit', '5672', 'guest', 'guest', '/');
        $this->channel = $this->connection->channel();

        /*
            name: $queue
            passive: false
            durable: true // the queue will survive server restarts
            exclusive: false // the queue can be accessed in other channels
            auto_delete: false //the queue won't be deleted once the channel is closed.
        */
        $this->channel->queue_declare($this->queue, false, true, false, false);
        /*
    name: $exchange
    type: direct
    passive: false
    durable: true // the exchange will survive server restarts
    auto_delete: false //the exchange won't be deleted once the channel is closed.
*/
        $this->channel->exchange_declare($this->exchange, 'direct', false, true, false);
        $this->channel->queue_bind($this->queue, $this->exchange);

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