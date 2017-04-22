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
/**
 * Elastic handler
 */
$config = [
    'host'      => 'de65d406293de24f3eb75085a9f9399a.us-east-1.aws.found.io',
    'port'      => 9243,
    'username'  => 'elastic',
    'password'  => 'AQzdVOMZsbBbVaw9Pd1NRfej',
    'transport' => 'https',
];
$client = new \Elastica\Client($config);

$elasticSearchHandler = new \Monolog\Handler\ElasticSearchHandler($client, []);

$elastiHandler = new \Monolog\Formatter\ElasticaFormatter('attlaz', 'json');

$elasticSearchHandler->setFormatter($elastiHandler);
$logger->pushHandler($elasticSearchHandler);

\Monolog\ErrorHandler::register($logger);

$endPointFile = BP_WORKER . '/../project/src/endpoint.php';
$projectChannel = new \Attlaz\Framework\App\ProjectChannel($endPointFile, $logger);

$settings = \Attlaz\Framework\Model\Settings::fromFile(BP_WORKER . '/../config.yml');


$app = new \Attlaz\Worker\Worker($settings, $projectChannel, $logger);
$app->listen();
