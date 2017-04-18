<?php
declare(strict_types=1);

namespace Attlaz\Manager\Handler;

use Attlaz\Framework\App\Logger;

use Attlaz\Queue\Queue;

class Handler
{

    /** @var Logger */
    protected $logger;

    /** @var  Queue */
    protected $queue;

    public function __construct(Logger $logger)
    {

        $this->logger = $logger;
    }

    public function setQueue(Queue $queue)
    {
        $this->queue = $queue;
    }

}