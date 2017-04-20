<?php

class ErrorHandler
{
    private $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke(Slim\Http\Request $request, Slim\Http\Response $response, \Throwable $exception)
    {


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

        $response = $response->withStatus(500)
                             ->withHeader('Content-Type', 'application/json');

        return $response->write(json_encode([
            'success' => false,
            'content' => 'Something went wrong: ' . $exception->getMessage(),
            'request' => $logRequest,
        ]));
//        return $response
//            ->withStatus(500)
//            ->withHeader('Content-Type', 'text/html')
//            ->write('Something went wrong!');
    }

}
