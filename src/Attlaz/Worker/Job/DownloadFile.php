<?php
declare(strict_types=1);

namespace Attlaz\Worker\Job;

use Attlaz\Worker\Model\JobCommand;
use GuzzleHttp\Client;

class DownloadFile extends JobCommand
{
    public function __invoke(string $url): string
    {
        $client = new Client();
        $res = $client->request('GET', $url, []);

        $body = $res->getBody()
                    ->getContents();

        return base64_encode($body);

// Send an asynchronous request.
//        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://httpbin.org');
//        $promise = $client->sendAsync($request)->then(function ($response) {
//            echo 'I completed! ' . $response->getBody();
//        });
//        $promise->wait();
    }
}