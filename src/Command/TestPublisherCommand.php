<?php
declare(strict_types=1);
/**
 * http://symfony.com/doc/master/console.html
 */

namespace Attlaz\Core\Command;

use OldSound\RabbitMqBundle\Command\RpcServerCommand;
use OldSound\RabbitMqBundle\RabbitMq\RpcServer;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TestPublisherCommand extends BaseCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('dev:publisher')
             ->addArgument('message', InputArgument::REQUIRED)
             ->addArgument('qty', InputArgument::OPTIONAL)
             ->setDescription('Send message to queue')
             ->setHelp('This command allows you send a message to queue');
    }

    /** @var  OutputInterface */
    private $output;

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $this->output = $output;

        $messageText = $input->getArgument('message');

        $repeat = 1;
        $qty = $input->getArgument('qty');
        if ($qty !== null) {
            $qty = intval($qty);
            if (is_int($qty)) {
                $repeat = $qty;
            }
        }

        for ($i = 0; $i < $repeat; $i++) {
            $this->send($messageText);
            usleep(10);
        }
        $this->stop();
    }

    private function send(string $message)
    {
        $this->output->writeln('Send "' . $message . '" to queue');

        try {
            $channel = $this->getChannel();
            // $messageBody = implode(' ', array_slice($argv, 1));
            $message = new AMQPMessage($message, [
                'content_type'  => 'text/plain',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ]);
            $channel->basic_publish($message, $this->getExchange());
        } catch (\Exception $ex) {
            $this->output->writeln('<error>EX while sending message: ' . $ex->getMessage() . '</error>');
            //TODO: retry in some seconds
            $this->stop();
            sleep(5);

        }

    }

}