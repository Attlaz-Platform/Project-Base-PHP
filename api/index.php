<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BP', dirname(__FILE__));

require BP . '/ErrorHandler.php';
require BP . '/vendor/autoload.php';
require BP . '/../src/vendor/autoload.php';

/**
 * Initialize Logger
 */
$logger = new \Attlaz\Framework\App\Logger('Attlaz Api');

$slackWebhook = 'https://hooks.slack.com/services/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';
$slackHandler = new \Monolog\Handler\SlackWebhookHandler($slackWebhook, null, null, true, null, false, true, \Monolog\Logger::INFO);
$logger->pushHandler($slackHandler);

\Monolog\ErrorHandler::register($logger);

/**
 * Start application
 */
$c = new \Slim\Container();
$c['errorHandler'] = function ($c) use ($logger) {
    return new ErrorHandler($logger);
};
$c['phpErrorHandler'] = function ($c) use ($logger) {
    return new ErrorHandler($logger);
};
$c['notFoundHandler'] = function ($c) use ($logger) {
    //TODO: return header 404
    return new ErrorHandler($logger);
};
$c['notAllowedHandler'] = function ($c) use ($logger) {
    //TODO: return header 405
    return new ErrorHandler($logger);
};
$app = new Slim\App($c);

$app->post('/task/execute', function (Slim\Http\Request $request, Slim\Http\Response $response, array $args) use ($logger) {

    $logRequest = [
        'method' => $request->getMethod(),
        'params' => $request->getParams(),
        'body'   => $request->getBody()
                            ->__toString(),
    ];
    $logger->info('Incoming request', [
        'request' => $logRequest,
    ]);
    $response = $response->withHeader('Content-Type', 'application/json');

    $awaitResponse = $request->getQueryParam('wait');
    if (!is_null($awaitResponse)) {
        $awaitResponse = true;
    } else {
        $awaitResponse = false;
    }

    $strTask = $request->getBody()
                       ->__toString();
    $cmd = new \Attlaz\Framework\Serialization\DeserializeTaskFromString();
    $task = $cmd->__invoke($strTask);

    $settings = new \Attlaz\Framework\Model\Settings();
    $settings->queue_job_host = 'queue';
    $settings->queue_job_name = 'task';
    $settings->queue_job_port = 5672;
    $settings->queue_job_user = 'guest';
    $settings->queue_job_password = 'guest';

    $manager = new \Attlaz\Manager\Manager($settings, $logger);

    if ($awaitResponse) {
        $taskResult = $manager->sendTaskWithResult($task);

        $cmd = new \Attlaz\Framework\Serialization\SerializeTaskResult();
        $serializedTaskResult = $cmd->__invoke($taskResult);
        $logger->warning('Result: ' . $serializedTaskResult);

        $response->getBody()
                 ->write($serializedTaskResult);
    } else {
        $manager->sendTaskWithoutResult($task);

    }

    return $response;
});

$app->run();


