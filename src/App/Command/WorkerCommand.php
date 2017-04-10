<?php
declare(strict_types=1);

namespace Attlaz\Core\App\Command;

use Attlaz\Core\App\Command\BaseCommand;
use Attlaz\Core\Model\Manager\ReplyManager;
use Attlaz\Core\Model\Settings;
use Attlaz\Core\Model\Worker\Worker;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends BaseCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('worker:start')
             ->setDescription('Send message to queue')
             ->setHelp('This command allows you send a message to queue');
    }

    /** @var  OutputInterface */
    private $output;

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        $this->output = $output;

        $settings = new Settings();
        $settings->queue_host = 'rabbit';
        $settings->queue_port = 5672;
        $settings->queue_user = 'guest';
        $settings->queue_password = 'guest';
        $settings->queue_queue = 'task';

        $logger = new Logger('Attlaz');
        $logger->pushHandler(new StreamHandler(STDOUT));

        $worker = new Worker($settings, $logger);
        $worker->listen();

    }

}