<?php
declare(strict_types=1);

namespace Attlaz\Api\Command\System;

use Attlaz\Api\Command\ApiCommand;

use Slim\Http\Request;
use Slim\Http\Response;

class BranchesCommand extends ApiCommand
{

    public function __invoke(Request $request, Response $response): Response
    {
        $data = [];
        $data[] = [
            'id'    => 'Cw8FBA4DAAg',
            'code'  => 'verlichting',
            'title' => 'Verlichting',
        ];
        $data[] = [
            'id'    => 'AQsJAAwDDQQ',
            'code'  => 'verlichting',
            'title' => 'Verlichting',
        ];

        return $response->withJson($data);

    }

}