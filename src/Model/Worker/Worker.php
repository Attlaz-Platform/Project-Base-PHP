<?php
declare(strict_types=1);

namespace Attlaz\Core\Model\Worker;

use Attlaz\Core\Command\DeserializeTaskFromString;
use Attlaz\Core\Command\ExecuteTask;
use Attlaz\Core\Command\SerializeTaskResult;
use Attlaz\Core\Helper\DateTimeHelper;
use Attlaz\Core\Model\Settings;
use Attlaz\Core\Model\Task;
use Attlaz\Core\Model\TaskResult;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class Worker
{
    private $settings;
    private $logger;
    /** @var  AMQPChannel */
    private $channel;

    private $consumer_tag;
    private $name = '';

    public function __construct(Settings $settings, LoggerInterface $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Listens for incoming messages
     */
    public function listen(): void
    {

        $this->logger->debug('Start listening');
        $connection = new AMQPStreamConnection($this->settings->queue_host, $this->settings->queue_port, $this->settings->queue_user, $this->settings->queue_password);
        $this->channel = $connection->channel();

        $this->channel->queue_declare($this->settings->queue_queue, false, false, false, false);

        $this->channel->basic_qos(null, 1, null);

        $this->consumer_tag = $this->channel->basic_consume($this->settings->queue_queue, $this->name, false, false, false, false, [
            $this,
            'onMessageReceive',
        ]);

        while (count($this->channel->callbacks)) {
            $this->channel->wait();
        }
        $this->logger->debug('Stop listening');
        $this->channel->close();
        $connection->close();
    }

    /**
     * Executes when a message is received.
     *
     * @param AMQPMessage $message
     */
    public function onMessageReceive(AMQPMessage $message): void
    {


        $this->logger->debug('Incoming message');

        $messageBody = $message->getBody();

        $taskResult = $this->handleMessage($messageBody);

        if ($this->messageExpectReply($message)) {
            $this->sendReply($taskResult, $message);
        }

        /*
         * Acknowledging the message
         */
        $this->channel->basic_ack($message->delivery_info['delivery_tag']);
    }

    private function messageExpectReply(AMQPMessage $message): bool
    {
        return $message->has('correlation_id') && $message->has('reply_to');
    }

    private function getErrorTaskResult(\Throwable $error): TaskResult
    {
        return new TaskResult(null, 'Unable to execute task: ' . $error->getMessage(), false);
    }

    private function decodeBodyToTask(string $body): Task
    {
        return (new DeserializeTaskFromString)($body);
    }

    private function executeTask(Task $task): TaskResult
    {

        $cmd = new ExecuteTask();

        $taskResult = $cmd->__invoke($task, $this->logger);

        return $taskResult;

    }

    private function sendReply(TaskResult $taskResult, AMQPMessage $originalMessage): void
    {

        /*
         * Creating a reply message with the same correlation id than the incoming message
         */
        $correlationId = $originalMessage->get('correlation_id');
        $client = $originalMessage->get('reply_to');
        /** @var AMQPChannel $channel */
        $channel = $originalMessage->delivery_info['channel'];

        $cmd = new SerializeTaskResult();
        $serializedTaskResult = $cmd->__invoke($taskResult);

        $msg = new AMQPMessage($serializedTaskResult, ['correlation_id' => $correlationId]);

        /*
         * Publishing to the same channel from the incoming message
         */

        $channel->basic_publish($msg, '', $client);
    }

    /**
     * @param $messageBody
     * @return TaskResult
     */
    private function handleMessage(string $messageBody): TaskResult
    {
        $received = DateTimeHelper::getNow();
        try {
            $task = $this->decodeBodyToTask($messageBody);
            if ($task->getMethod() === 'quit') {
                $this->cancel();
            }

            $taskResult = $this->executeTask($task);
        } catch (\Throwable $ex) {
            $this->logger->error('Unable to process message: ' . $ex->getMessage());
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