#!/usr/bin/env php
<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BP_WORKER', dirname(__FILE__));

require BP_WORKER . '/../src/vendor/autoload.php';

$logger = new \Attlaz\Framework\App\Logger('Attlaz');
$webhookUrl = 'https://hooks.slack.com/services/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';
$slackHandler = new \Monolog\Handler\SlackWebhookHandler($webhookUrl, null, null, true, null, false, true, \Monolog\Logger::INFO);
$logger->pushHandler($slackHandler);

$streamHandler = new Monolog\Handler\StreamHandler('php://stdout', \Monolog\Logger::DEBUG);

//$format = '[%datetime%] %channel%.%level_name%: %message%. PHP_EOL .%context%' . PHP_EOL . '%extra%\n';

$streamHandlerFormatter = new \Bramus\Monolog\Formatter\ColoredLineFormatter();

$streamHandlerFormatter->allowInlineLineBreaks(true);
$streamHandlerFormatter->includeStacktraces(true);

$streamHandler->setFormatter($streamHandlerFormatter);
$logger->pushHandler($streamHandler);

\Monolog\ErrorHandler::register($logger);

$projectRegistrationFile = BP_WORKER . '/../project/src/registration.php';
if (!file_exists($projectRegistrationFile)) {
    $logger->error('Unable to load project registration file, file does not exist');
} else {
    require BP_WORKER . '/../project/src/registration.php';

    $project = \Attlaz\Framework\App\ProjectRegistrar::getProject();
    $settings = \Attlaz\Framework\Model\Settings::fromFile(BP_WORKER . '/../config.yml');

    /** @var \Psr\Log\LoggerInterface $projectLogger */
    $projectLogger = $project->getContainer()
                             ->get(\Psr\Log\LoggerInterface::class);
    if ($projectLogger instanceof \Monolog\Logger || $projectLogger instanceof Attlaz\Framework\App\Logger) {
        $projectLogger->pushHandler($slackHandler);
        $projectLogger->pushHandler($streamHandler);
    }

    $executeTaskHelper = new \Attlaz\Worker\Helper\ExecuteTaskHelper($project, $projectLogger);

    if (false) {
        $task = new \Attlaz\Framework\Model\Task('syncCatalog', [
            'externalIds' => [
                11505,
                15706,
                14757,
                8793,
            ],
        ]);
        $executeTaskHelper->__invoke($task);

        return;
    }
    $app = new \Attlaz\Worker\Worker($settings, $executeTaskHelper, $projectLogger);
    $app->listen();
}