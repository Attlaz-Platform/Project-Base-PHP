<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BP', dirname(__FILE__));

require BP . '/vendor/autoload.php';
require BP . '/../src/vendor/autoload.php';

/**
 * Initialize Logger
 */
$logger = new \Attlaz\Framework\App\Logger('Attlaz Api');

$slackWebhook = 'https://hooks.slack.com/services/REDACTED';
$slackHandler = new \Monolog\Handler\SlackWebhookHandler($slackWebhook, null, null, true, null, false, true, \Monolog\Logger::INFO);
$logger->pushHandler($slackHandler);

\Monolog\ErrorHandler::register($logger);

/**
 * Start application
 */
$api = new \Attlaz\Api\Api($logger);
$api->run();


