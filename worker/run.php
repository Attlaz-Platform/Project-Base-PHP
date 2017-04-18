#!/usr/bin/env php
<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BP', dirname(__FILE__));

require BP . '/../src/vendor/autoload.php';

$logger = new \Attlaz\Framework\App\Logger('Attlaz');
$webhookUrl = 'https://hooks.slack.com/services/REDACTED';
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

$settings = \Attlaz\Framework\Model\Settings::fromFile(BP . '/../config.yml');
$app = new \Attlaz\Worker\Worker($settings, $logger);
$app->listen();