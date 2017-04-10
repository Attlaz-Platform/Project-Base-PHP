<?php
declare(strict_types=1);

namespace Attlaz\Core\App\Command;

use Attlaz\Core\App\Command\BaseCommand;
use PhpAmqpLib\Channel\AMQPChannel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SysInfoCommand extends BaseCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('sys:info')
             ->setDescription('Show system information');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        var_dump($this->getInfo());

    }

    private function getInfo(): array
    {
        $info = [];
        $this->initChannel();
        $serverProperties = $this->connection->getServerProperties();

        /** @var AMQPChannel[] $channels */
//        $channels = $this->connection->channels;
//        foreach($channels as $channel)
//        {
//            var_dump($channel->queue_bind());
//        }

        $info['server'] = $serverProperties['product'][1] . ' ' . $serverProperties['version'][1];
        $info['queues'] = [];
        //TODO: get queue names from server
        $queues = [
            $this->getQueueName() => $this->getChannel()
                                          ->queue_declare($this->getQueueName(), false, true, false, false),
            'rpc_queue'           => $this->getChannel()
                                          ->queue_declare('rpc_queue', false, false, false, false),

        ];
        foreach ($queues as $queueName => $queue) {


            $info['queues'][$queueName] = [
                'consumers' => $queue[2],
                'messages'  => $queue[1],

            ];
        }

        return $info;

    }

}