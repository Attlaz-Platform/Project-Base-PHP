<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/vendor/autoload.php';

header("Content-type:application/json");

$request = file_get_contents("php://input");

$cmd = new \Attlaz\Framework\Serialization\DeserializeTaskFromString();

$task = $cmd->__invoke($request);

$settings = new \Attlaz\Queue\Model\Settings();
$settings->queue_job_host = 'queue';
$settings->queue_job_name = 'task';
$settings->queue_job_port = 5672;
$settings->queue_job_user = 'guest';
$settings->queue_job_password = 'guest';

$logger = new \Attlaz\Framework\App\Logger('Attlaz Api');
$logger->addGlobalContext('app', 'api');
//$streamHandler = new \Monolog\Handler\StreamHandler(fopen('php://output', 'w'));
//$streamHandler->setFormatter(new \Monolog\Formatter\HtmlFormatter());
//$logger->pushHandler($streamHandler);

\Monolog\ErrorHandler::register($logger);

$manager = new \Attlaz\Manager\Manager($settings, $logger);

//if (false) {
//    $manager->sendTaskWithoutResult($task);
//
//    return;
//}
$send = \Attlaz\Framework\Helper\DateTimeHelper::getNow();
$response = $manager->sendTaskWithResult($task);

$cmd = new \Attlaz\Framework\Serialization\SerializeTaskResult();
echo $cmd->__invoke($response);