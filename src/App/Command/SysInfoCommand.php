<?php
declare(strict_types=1);

namespace Attlaz\Core\App\Command;

use Attlaz\Core\App\Command\BaseCommand;
use Attlaz\Core\Model\Settings;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use PhpAmqpLib\Channel\AMQPChannel;
use Psr\Log\LoggerInterface;
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

        /** @var Settings $settings */
        $settings = $this->getContainer()
                         ->get('settings');

        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()
                       ->get('logger');

        $this->initChannel($settings);

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
            'task' => $this->getChannel()
                           ->queue_declare('task', false, true, false, false),

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