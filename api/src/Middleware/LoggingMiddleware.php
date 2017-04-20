<?php
declare(strict_types=1);

namespace Attlaz\Api\Middleware;

use Attlaz\Framework\App\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LoggingMiddleware
{
    private $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next): ResponseInterface
    {
        $logRequest = [
            'method' => $request->getMethod(),
            'params' => $request->getQueryParams(),
            'body'   => $request->getBody()
                                ->__toString(),
        ];
        $this->logger->info('Incoming request', [
            'request' => $logRequest,
        ]);

        $response = $next($request, $response);

        return $response;
    }
}