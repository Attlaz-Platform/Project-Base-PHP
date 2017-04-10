<?php
declare(strict_types=1);

namespace Attlaz\Core;

use Attlaz\Core\Command\ManagerCommand;
use Attlaz\Core\Command\SysInfoCommand;
use Attlaz\Core\Command\TestConsumerCommand;
use Attlaz\Core\Command\TestPublisherCommand;
use Attlaz\Core\Command\WorkerCommand;
use Symfony\Component\Console\Application;

class App
{
    private $application;

    public function run(): void
    {


        $this->application = new Application();
        $this->application->add(new TestPublisherCommand());
        $this->application->add(new TestConsumerCommand());
        $this->application->add(new SysInfoCommand());
        $this->application->add(new ManagerCommand());
        $this->application->add(new WorkerCommand());
        $this->application->run();
    }

    private function init(): void
    {
        // $containerBuilder = new ContainerBuilder();
    }
}