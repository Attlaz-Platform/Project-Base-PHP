<?php
declare(strict_types=1);

namespace Attlaz\Core\App;

use Attlaz\Core\App\Command\ManagerCommand;
use Attlaz\Core\App\Command\SysInfoCommand;
use Attlaz\Core\App\Command\TestConsumerCommand;
use Attlaz\Core\App\Command\TestPublisherCommand;
use Attlaz\Core\App\Command\WorkerCommand;
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