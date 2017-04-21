<?php
declare(strict_types=1);

namespace Attlaz\Api;

use Attlaz\Api\Command\StatusCommand;
use Attlaz\Api\Command\System\BranchesCommand;
use Attlaz\Api\Command\TaskExecuteCommand;
use Attlaz\Api\Middleware\LoggingMiddleware;
use Attlaz\Framework\App\Logger;

use Slim\App;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class Api
{
    /** @var Logger */
    private $logger;
    /** @var  App */
    private $app;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    private function initializeApp()
    {
        $c = new Container();
        $c['errorHandler'] = function ($c) {
            return new ErrorHandler($this->logger);
        };
        $c['phpErrorHandler'] = function ($c) {
            return new ErrorHandler($this->logger);
        };
        $c['notFoundHandler'] = function ($c) {
            $errorHandler = new ErrorHandler($this->logger);
            $errorHandler->setStatusCode(404);

            return $errorHandler;
        };
        $c['notAllowedHandler'] = function ($c) {
            $errorHandler = new ErrorHandler($this->logger);
            $errorHandler->setStatusCode(405);

            return $errorHandler;
        };

        $this->app = new App($c);
        $this->app->add(new LoggingMiddleware($this->logger));
    }

    public function run()
    {
        $this->initializeApp();

        $logger = $this->logger;
        $this->app->post('/task/execute', function (Request $request, Response $response) use ($logger) {
            $command = new TaskExecuteCommand($logger);

            return $command->__invoke($request, $response);
        });
        $this->app->get('/system/branches', function (Request $request, Response $response) use ($logger) {
            $command = new BranchesCommand($logger);

            return $command->__invoke($request, $response);
        });
        $this->app->get('/system/commands', function (Request $request, Response $response) use ($logger) {
            $command = new BranchesCommand($logger);

            return $command->__invoke($request, $response);
        });
        $this->app->get('/system/command/{commandId}/executions', function (Request $request, Response $response) use ($logger) {
            $commandId = $request->getParam('commandId');
            $command = new BranchesCommand($logger);

            return $command->__invoke($request, $response);
        });
        $this->app->get('/system/command/{commandId}/execution/{executionId}/logs', function (Request $request, Response $response) use ($logger) {
            $commandId = $request->getParam('commandId');
            $executionId = $request->getParam('executionId');
            $command = new BranchesCommand($logger);

            return $command->__invoke($request, $response);
        });
//        $this->app->get('/status', function (Request $request, Response $response) use ($logger) {
//            $command = new StatusCommand($logger);
//
//            return $command->__invoke($request, $response);
//        });

        $this->app->run();
    }
}