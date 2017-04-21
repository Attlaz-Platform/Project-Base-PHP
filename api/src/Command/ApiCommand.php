<?php
declare(strict_types=1);

namespace Attlaz\Api\Command;

use Attlaz\Framework\App\Logger;
use Attlaz\Framework\Model\Settings;
use Attlaz\Manager\Manager;
use Slim\Http\Request;
use Slim\Http\Response;

abstract class ApiCommand
{
    protected $logger;
    private $manager;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    protected final function getManager(): Manager
    {
        if (\is_null($this->manager)) {
            //TODO: remove hardcoded settings
            $settings = new Settings();
            $settings->queue_job_host = 'queue';
            $settings->queue_job_name = 'task';
            $settings->queue_job_port = 5672;
            $settings->queue_job_user = 'guest';
            $settings->queue_job_password = 'guest';

            $this->manager = new Manager($settings, $this->logger);
        }

        return $this->manager;
    }

    abstract public function __invoke(Request $request, Response $response): Response;

}