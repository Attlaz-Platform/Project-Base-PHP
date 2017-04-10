<?php
declare(strict_types=1);

namespace Attlaz\Core\Command;

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
        $queue = $this->getChannel()
                      ->queue_declare($this->getQueueName(), false, true, false, false);

        $info['server'] = $serverProperties['product'][1] . ' ' . $serverProperties['version'][1];
        $info['queue'] = [
            'consumers' => $queue[2],
            'messages'  => $queue[1],

        ];

        return $info;

    }

}