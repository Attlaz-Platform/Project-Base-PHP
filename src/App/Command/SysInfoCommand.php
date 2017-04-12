<?php
declare(strict_types=1);

namespace Attlaz\Core\App\Command;

use Attlaz\Core\Model\Settings;
use PhpAmqpLib\Connection\AMQPStreamConnection;
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

        $output->write(json_encode($this->getInfo(), JSON_PRETTY_PRINT));

    }

    private function getInfo(): array
    {


        /** @var Settings $settings */
        $settings = $this->getContainer()
                         ->get('settings');

        return $this->getQueueInfo($settings);

    }

    private function getQueueInfo(Settings $settings): array
    {

        $connection = new AMQPStreamConnection($settings->queue_job_host, $settings->queue_job_port, $settings->queue_job_user, $settings->queue_job_password);

        $serverProperties = $connection->getServerProperties();

        $info = [];
        $info['server'] = $serverProperties['product'][1] . ' ' . $serverProperties['version'][1];
        $info['queues'] = [];
        //TODO: get queue names from server

        $channel = $connection->channel();
        $queue = $channel->queue_declare($settings->queue_job_name, false, true, false, false);
        $channel->close();
        $connection->close();

        $info['queues']['jobs'] = [
            'consumers' => $queue[2],
            'messages'  => $queue[1],

        ];

        return $info;

    }

}