<?php
declare(strict_types=1);

namespace Attlaz\Worker;

use Attlaz\Framework\App\Logger;
use Attlaz\Framework\Helper\DateTimeHelper;
use Attlaz\Framework\Model\Task;
use Attlaz\Framework\Model\TaskResult;
use Attlaz\Framework\Serialization\DeserializeTaskFromString;
use Attlaz\Framework\Serialization\SerializeTaskResult;

use Attlaz\Framework\Model\Settings;
use Attlaz\Queue\Queue;
use Attlaz\Worker\Helper\ExecuteTaskHelper;
use Attlaz\Worker\Helper\NameHelper;
use Monolog\Handler\AbstractHandler;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Exception\AMQPRuntimeException;
use PhpAmqpLib\Message\AMQPMessage;

class Worker
{
    private $settings;
    /** @var Logger */
    private $logger;
    /** @var  AMQPChannel */
    private $channel;

    private $consumer_tag;
    private $name = '';

    /** @var  Queue */
    private $queue;

    private $executeTaskHelper;

    public function __construct(Settings $settings, ExecuteTaskHelper $executeTaskHelper, Logger $logger)
    {
        $this->settings = $settings;
        $this->executeTaskHelper = $executeTaskHelper;
        $this->logger = $logger;
    }

    public function getName(): string
    {
        if (empty($this->name)) {
            $this->name = NameHelper::getRandomName();

        }

        return $this->name;
    }

    /**
     * Listens for incoming messages
     */
    public function listen(): void
    {
        try {
            $this->queue = new Queue($this->settings, $this->logger);

            $this->queue->connect();

            $this->channel = $this->queue->getChannel();

            $this->channel->basic_qos(null, 1, null);

            $this->consumer_tag = $this->channel->basic_consume($this->settings->queue_job_name, $this->getName(), false, false, false, false, [
                $this,
                'onMessageReceive',
            ]);

            $this->logger->addGlobalContext('queue', $this->settings->queue_job_name);
            $this->logger->addGlobalContext('consumer_tag', $this->consumer_tag);
            $this->logger->addGlobalContext('ip', gethostbyname(gethostname()));

            $this->logger->debug('Start listening');

            while (count($this->channel->callbacks)) {
                try {
//                    $this->channel->wait(null, false, $timeout);
                    $this->channel->wait();
                } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                    $this->logger->error('Queue timeout, reconnecting (' . $e->getMessage() . ')');

                    $this->queue->disconnect();

                } catch (AMQPRuntimeException $ex) {
                    $this->logger->error('Queue runtime error (' . $ex->getMessage() . ')');
                    //TODO: make sure all connections are closed
                    $this->disconnect();

                    $this->listen();

                    return;

                } catch (\Throwable $ex) {
                    $this->logger->error('Queue unknown error (' . $ex->getMessage() . ')');
                    throw $ex;
                }

            }
            $this->logger->debug('Stop listening');

            $this->queue->disconnect();
        } catch (\Exception $ex) {
            $this->logger->emergency('Queue error: ' . $ex->getMessage());
        }

    }

    private function disconnect()
    {
        try {
            $this->queue->disconnect();
        } catch (\Exception $ex) {
            $this->logger->error('Exception while disconnecting (' . $ex->getMessage() . ')');
        }

    }

    /**
     * Executes when a message is received.
     *
     * @param AMQPMessage $message
     */
    public function onMessageReceive(AMQPMessage $message): void
    {


        $messageBody = $message->getBody();

        $taskResult = $this->handleMessage($messageBody);

        if ($this->messageExpectReply($message)) {
            $this->sendReply($taskResult, $message);
        }

        /*
         * Acknowledging the message
         */
        $this->channel->basic_ack($message->delivery_info['delivery_tag']);

        //$message->delivery_info['channel']->basic_nack($message->delivery_info['delivery_tag']);

        //Flush logging handlers
        //TODO: separate this from the worker
        try {
            $handlers = $this->logger->getHandlers();
            foreach ($handlers as $handler) {
                if ($handler instanceof AbstractHandler) {
                    $handler->close();
                }

            }
        } catch (\Throwable $ex) {
            $this->logger->error($ex->getMessage());
        }

    }

    private function messageExpectReply(AMQPMessage $message): bool
    {
        return $message->has('correlation_id') && $message->has('reply_to');
    }

    private function getErrorTaskResult(\Throwable $error): TaskResult
    {
        return new TaskResult(new Task('unknown'), 'Unable to execute task: ' . $error->getMessage(), false);
    }

    private function decodeBodyToTask(string $body): Task
    {
        return (new DeserializeTaskFromString())($body);
    }

    private function executeTask(Task $task): TaskResult
    {


        $taskResult = $this->executeTaskHelper->__invoke($task);

        return $taskResult;

    }

    private function sendReply(TaskResult $taskResult, AMQPMessage $originalMessage): void
    {

        /*
         * Creating a reply message with the same correlation id than the incoming message
         */
        $correlationId = $originalMessage->get('correlation_id');
        $replyQueueName = (string)$originalMessage->get('reply_to');

//        /** @var AMQPChannel $channel */
//        $channel = $originalMessage->delivery_info['channel'];

        $cmd = new SerializeTaskResult();
        $serializedTaskResult = $cmd->__invoke($taskResult);

        $msg = new AMQPMessage($serializedTaskResult, ['correlation_id' => $correlationId]);

        /*
         * Publishing to the same channel from the incoming message
         */

        $this->queue->publishMessage($msg, $replyQueueName);
    }

    private function handleMessage(string $messageBody): TaskResult
    {
        $this->logger->debug('Incoming message', [
            'body'         => $messageBody,
            'queue'        => $this->settings->queue_job_name,
            'consumer_tag' => $this->consumer_tag,
        ]);
        $received = DateTimeHelper::getNow();
        try {
            $task = $this->decodeBodyToTask($messageBody);
            if ($task->getMethod() === 'quit') {
                $this->cancel();
                //$this->queue->disconnect();
                $taskResult = new TaskResult($task, '', true);
            } else {
                $taskResult = $this->executeTask($task);
            }

        } catch (\Throwable $ex) {
            $this->logger->error('Unable to process message: ' . $ex->getMessage(), [
                'queue'        => $this->settings->queue_job_name,
                'consumer_tag' => $this->consumer_tag,
            ]);
            $taskResult = $this->getErrorTaskResult($ex);
        }

        $responded = DateTimeHelper::getNow();
        $taskResult->setReceived($received);
        $taskResult->setResponded($responded);

        return $taskResult;
    }

    private function cancel()
    {
        $this->channel->basic_cancel($this->consumer_tag);

    }

}