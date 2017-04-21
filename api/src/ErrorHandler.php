<?php
declare(strict_types=1);

namespace Attlaz\Api;

use Psr\Log\LoggerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class ErrorHandler
{
    private $logger;
    private $statusCode = 500;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function setStatusCode(int $statusCode)
    {
        $this->statusCode = $statusCode;
    }

    public function __invoke(Request $request, Response $response, \Throwable $exception = null)
    {


        if (\is_null($exception)) {
            $exception = new \Exception();
        }
        $logRequest = [
            'method' => $request->getMethod(),
            'params' => $request->getQueryParams(),
            'body'   => $request->getBody()
                                ->__toString(),
        ];
        $this->logger->error($exception->getMessage(), [
            'request' => $logRequest,
            'ex'      => $exception->getTraceAsString(),
        ]);

        $response = $response->withStatus($this->statusCode)
                             ->withHeader('Content-Type', 'application/json');

        return $response->write(json_encode([
            'success' => false,
            'content' => 'Something went wrong: ' . $exception->getMessage(),
            'request' => $logRequest,
        ]));

    }

}
