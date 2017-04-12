<?php
declare(strict_types=1);

namespace Attlaz\Core\App\Command;

use Attlaz\Core\Helper\DateTimeHelper;
use Attlaz\Core\Model\Manager\NoReplyManager;
use Attlaz\Core\Model\Manager\ReplyManager;
use Attlaz\Core\Model\Settings;
use Attlaz\Core\Model\Task;
use Attlaz\Core\Model\TaskResult;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ManagerCommand extends BaseCommand
{

    protected function configure()
    {
        parent::configure();
        $this->setName('manager:start')
             ->addArgument('message', InputArgument::REQUIRED)
             ->setDescription('Send message to queue')
             ->setHelp('This command allows you send a message to queue');
    }

    /** @var  OutputInterface */
    private $output;

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Settings $settings */
        $settings = $this->getContainer()
                         ->get('settings');

        /** @var LoggerInterface $logger */
        $logger = $this->getContainer()
                       ->get('logger');

        $this->output = $output;

        $messageText = (string)$input->getArgument('message');

        //Send task and expect result
        $manager = new ReplyManager($settings, $logger);
//        $task = new Task('dummy', ['input' => $messageText]);
        $task = new Task('download', ['url' => 'http://ipv4.download.thinkbroadband.com/10MB.zip']);

        $send = DateTimeHelper::getNow();
        $result = $manager->execute($task);

        file_put_contents('10MB.zip', base64_decode($result->getData()));

        $received = DateTimeHelper::getNow();
        $debug = $this->debug($result, $send, $received);
        $this->output->writeln(json_encode($debug, JSON_PRETTY_PRINT));

//        //Send task without result
//        $manager = new NoReplyManager($settings, $logger);
//        $task = new Task('dummy', ['input' => $messageText]);
//
//        $send = DateTimeHelper::getNow();
//        $manager->execute($task);
//
//        $this->output->writeln('Done');
//
//        //Send multiple tasks and combine results
//        //TODO: implement
//
//        $manager = new NoReplyManager($settings, $logger);
//        $task = new Task('quit', ['input' => $messageText]);
//
//        $send = DateTimeHelper::getNow();
//        $manager->execute($task);
//
//        $this->output->writeln('Done');

    }

    private function debug(TaskResult $taskResult, \DateTime $send, \DateTime $received): array
    {
        $workerProcessTime = $taskResult->getReceived()
                                        ->diff($taskResult->getResponded());
        $totalProcessTime = $send->diff($received);
        $response[] = [
            'output             ' => (string)$taskResult->getData(),
            'worker received    ' => $taskResult->getReceived()
                                                ->format('Y-m-d H:i:s u'),
            'worker responded   ' => $taskResult->getResponded()
                                                ->format('Y-m-d H:i:s u'),

            'worker time        ' => $workerProcessTime->format('%h:%i %ss %F'),
            'manager send       ' => $send->format('Y-m-d H:i:s u'),
            'manager responded  ' => $received->format('Y-m-d H:i:s u'),
            'manager time       ' => $totalProcessTime->format('%h:%i %ss %F'),
        ];

        return $response;
    }

}