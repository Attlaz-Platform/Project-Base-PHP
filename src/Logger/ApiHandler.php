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

    public function __construct(Client $client, int $level = Logger::DEBUG, bool $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->client = $client;
    }

    protected function write(array $record)
    {
        $logEntry = new LogEntry($record['message'], strtolower($record['level_name']));
        $logEntry->date = $record['datetime'];
        $logEntry->context = $record['context'];
        if (isset($record['extra']['execution'])) {
            //TODO: what is the log entry type when no task execution is defined?
            $logEntry->context['taskexecution'] = $record['extra']['execution'];
            $logEntry->type = 'taskexecution';
        }

        //TODO: combine extra with context?
        try {
            $saved = $this->client->saveLog($logEntry);
        } catch (\Exception $ex) {
            echo 'Unable to save Log: ' . $ex->getMessage() . PHP_EOL;

            var_dump($logEntry);
        }
    }
}
