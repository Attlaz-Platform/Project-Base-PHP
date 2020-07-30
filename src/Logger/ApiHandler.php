<?php

declare(strict_types=1);

namespace Attlaz\Project\Logger;

use Attlaz\Client;
use Attlaz\Model\LogEntry;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

class ApiHandler extends AbstractProcessingHandler
{

    private $client;
    private $maxLogMessageLength = 5000;

    public function __construct(Client $client, int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->client = $client;
    }

    protected function write(array $record): void
    {
        try {
            if (isset($record['formatted'])) {
                $record = $record['formatted'];
            }

            //TODO: check message length, if its to long, break the message in parts or skip
            $logEntry = new LogEntry($record['message'], strtolower($record['level_name']));
            $logEntry->date = $record['datetime'];
            $logEntry->context = $record['context'];
            if (isset($record['extra']['execution'])) {
                //TODO: what is the log entry type when no task execution is defined?
                //                $logEntry->context['taskexecution'] = $record['extra']['execution'];
                //                $logEntry->type = 'taskexecution';

                $logEntry->tags[] = [
                    'key'   => 'taskexecution',
                    'value' => $record['extra']['execution'],
                ];
                $logEntry->tags[] = [
                    'key'   => 'type',
                    'value' => 'taskexecution',
                ];
            }

            if (\strlen($logEntry->message) > $this->maxLogMessageLength) {
                $logEntry->message = \substr($logEntry->message, 0, $this->maxLogMessageLength);
            }
            //TODO: combine extra with context?

            $logEntryId = $this->client->saveLog($logEntry);
        } catch (\Throwable $ex) {
            echo 'Unable to save Log: ' . $ex->getMessage() . PHP_EOL;
            // var_dump(\substr($logEntry->message, 0, 500));

            echo $ex->getTraceAsString() . \PHP_EOL;
        }
    }
}
