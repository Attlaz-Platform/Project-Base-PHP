<?php
declare(strict_types=1);

namespace Attlaz\Core\Model;

use Attlaz\Core\Command\DeserializeTaskFromString;
use Attlaz\Core\Command\ExecuteTask;
use Attlaz\Core\Command\SerializeTaskResult;
use Attlaz\Core\Helper\DateTimeHelper;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerInterface;

class Worker
{
    private $settings;
    private $logger;

    public function __construct(Settings $settings, LoggerInterface $logger)
    {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    /**
     * Listens for incoming messages
     */
    public function listen(): void
    {

        $this->logger->debug('Start listening');
        $connection = new AMQPStreamConnection($this->settings->queue_host, $this->settings->queue_port, $this->settings->queue_user, $this->settings->queue_password);
        $channel = $connection->channel();

        $channel->queue_declare($this->settings->queue_queue, false, false, false, false);

        $channel->basic_qos(null, 1, null);

        $channel->basic_consume($this->settings->queue_queue, '', false, false, false, false, [
            $this,
            'callback',
        ]);

        while (count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    /**
     * Executes when a message is received.
     *
     * @param AMQPMessage $req
     */
    public function callback(AMQPMessage $req): void
    {
        $received = DateTimeHelper::getNow();

        $this->logger->debug('Incoming message');
        try {
            $task = $this->decodeBodyToTask($req->body);
            $taskResult = $this->executeTask($task);
        } catch (\Throwable $ex) {
            $this->logger->error('Unable to process message: ' . $ex->getMessage());
            $taskResult = $this->getErrorTaskResult($ex);
        }
        $responded = DateTimeHelper::getNow();
        $taskResult->setReceived($received);
        $taskResult->setResponded($responded);

        $cmd = new SerializeTaskResult();
        $serializedTaskResult = $cmd->__invoke($taskResult);

        /*
         * Creating a reply message with the same correlation id than the incoming message
         */
        $msg = new AMQPMessage($serializedTaskResult, ['correlation_id' => $req->get('correlation_id')]);

        /** @var AMQPChannel $channel */
        $channel = $req->delivery_info['channel'];
        /*
         * Publishing to the same channel from the incoming message
         */
        $channel->basic_publish($msg, '', $req->get('reply_to'));

        /*
         * Acknowledging the message
         */
        $channel->basic_ack($req->delivery_info['delivery_tag']);
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

}