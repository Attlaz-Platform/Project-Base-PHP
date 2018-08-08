<?php
declare(strict_types=1);

namespace Attlaz\Project\App;

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
        $logEntry->context['taskexecution'] = $record['extra']['execution'];
        $logEntry->type = 'taskexecution';

        //TODO: combine extra with context?

        $saved = $this->client->saveLog($logEntry);

//        var_dump($logEntry);
//        var_dump($saved);
    }
}