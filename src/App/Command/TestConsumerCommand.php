<?php
declare(strict_types=1);

namespace Attlaz\Core\App\Command;

use Attlaz\Core\App\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestConsumerCommand extends BaseCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('dev:consumer')
             ->setDescription('Listen to message on queue')
             ->setHelp('This command allows you listen to message on to queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $consumerTag = 'consumer';

        $channel = $this->getChannel();

        $channel->basic_consume($this->getQueueName(), $consumerTag, false, false, false, false, [
            $this,
            'process_message',
        ]);
        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    private $handled = 0;

    function process_message($message)
    {

        echo 'Incoming: ' . $message->body . ' [' . date("Y-m-d H:i:s") . ']' . PHP_EOL;

        $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
        // Send a message with the string "quit" to cancel the consumer.
        if ($message->body === 'quit') {
            $message->delivery_info['channel']->basic_cancel($message->delivery_info['consumer_tag']);
        }
    }

}