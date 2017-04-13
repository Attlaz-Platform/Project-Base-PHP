<?php
declare(strict_types=1);

namespace Attlaz\Framework\App;

use Attlaz\Framework\App\Command\ManagerCommand;
use Attlaz\Framework\App\Command\SysInfoCommand;
use Attlaz\Framework\App\Command\WorkerCommand;
use Attlaz\Queue\Model\Settings;
use Monolog\Handler\SlackbotHandler;
use Monolog\Handler\SlackHandler;
use Monolog\Handler\SlackWebhookHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class App
{
    private $application;
    private $settings;
    private $containerBuilder;

    public function run(): void
    {


        $this->init();

        $this->application = new Application();

        $cmd = new SysInfoCommand();
        $cmd->setContainer($this->containerBuilder);
        $this->application->add($cmd);

        $cmd = new ManagerCommand();
        $cmd->setContainer($this->containerBuilder);
        $this->application->add($cmd);

        $cmd = new WorkerCommand();
        $cmd->setContainer($this->containerBuilder);
        $this->application->add($cmd);

        $this->application->setDefaultCommand($cmd->getName());
        $this->application->run();
    }

    private function init(): void
    {


        $this->containerBuilder = new ContainerBuilder();

        $this->settings = Settings::fromFile(__DIR__ . '/../../../env/config.yml');
        $this->containerBuilder->set('settings', $this->settings);

        $logger = new Logger('Attlaz');

        $logger->pushHandler(new StreamHandler(STDOUT));

        $userName = 'Attlaz @ ' . gethostname();
        $logger->pushHandler(new SlackWebhookHandler('https://hooks.slack.com/services/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', '#attlaz-log', $userName, true, null, true, true, Logger::DEBUG));

        $this->containerBuilder->set('logger', $logger);
    }
}