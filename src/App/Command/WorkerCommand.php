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
protected const ARG_WORKER_NAME = 'name';
    protected function configure()
    {
        parent::configure();
        $this->setName('worker:start')
             ->addArgument(self::ARG_WORKER_NAME, InputArgument::OPTIONAL)
             ->setDescription('Start worker')
             ->setHelp('This command starts a worker process');
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

        $workerName = null;
        if($input->hasArgument(self::ARG_WORKER_NAME))
        {
            $workerName = (string)$input->getArgument(self::ARG_WORKER_NAME);
        }




        $worker = new Worker($settings, $logger);
        if($workerName !== null)
        {
            $worker->setName($workerName);
        }


        $worker->listen();

    }

}