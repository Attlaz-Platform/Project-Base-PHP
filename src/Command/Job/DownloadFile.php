<?php
/**
 * Created by IntelliJ IDEA.
 * User: stijn
 * Date: 15 Aug 17
 * Time: 00:29
 */

namespace Attlaz\Core\Command\Job;

use GuzzleHttp\Client;

class DownloadFile
{
    public function __invoke(string $url): string
    {
        $client = new Client();
        $res = $client->request('GET', $url, []);
//        echo $res->getStatusCode();
//// "200"
        // var_dump($res->getHeaders());
// 'application/json; charset=utf8'
        $body = $res->getBody();

        // $body = utf8_encode($body);

        return base64_encode($body);

// {"type":"User"...'

// Send an asynchronous request.
//        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://httpbin.org');
//        $promise = $client->sendAsync($request)->then(function ($response) {
//            echo 'I completed! ' . $response->getBody();
//        });
//        $promise->wait();
    }
}