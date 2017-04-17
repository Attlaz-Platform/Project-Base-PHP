<?php
declare(strict_types=1);

namespace Attlaz\Framework\App\Command;

use Attlaz\Framework\Helper\DateTimeHelper;
use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Queue\Model\Manager\ReplyManager;
use Attlaz\Queue\Model\Settings;
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
             ->addArgument('task',

                 InputArgument::REQUIRED)
             ->setDescription('Put command to queue')
             ->setHelp('This command allows you put a command to queue');
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

        $command = (string)$input->getArgument('task');
        switch ($command) {
            case 'ping':
                $manager = new ReplyManager($settings, $logger);
                $task = new Task('ping', ['input' => 'Hello world']);
                $send = DateTimeHelper::getNow();
                $result = $manager->execute($task);
                $received = DateTimeHelper::getNow();
                $debug = $this->debug($result, $send, $received);

                break;
            case 'quit':
                $manager = new ReplyManager($settings, $logger);
                $task = new Task('quit');
                $manager->execute($task);

                break;
//            case 'example':
//                $manager = new ReplyManager($settings, $logger);
//                $task = new Task('example');
//                $manager->execute($task);
//
//                break;
        }

        //Send task and expect result
//        $manager = new ReplyManager($settings, $logger);
//        $task = new Task('ping', ['input' => $messageText]);
////        $task = new Task('download', ['url' => 'http://ipv4.download.thinkbroadband.com/10MB.zip']);
////
////        $send = DateTimeHelper::getNow();
//        $result = $manager->execute($task);
//
//        echo $result->getData() . PHP_EOL;
//
//        file_put_contents('10MB.zip', base64_decode($result->getData()));
//
//        $received = DateTimeHelper::getNow();
//        $debug = $this->debug($result, $send, $received);
//        $this->output->writeln(json_encode($debug, JSON_PRETTY_PRINT));

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