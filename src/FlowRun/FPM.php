<?php

declare(strict_types=1);

namespace Attlaz\Project\FlowRun;

use Attlaz\Project\Model\FlowRunRequest;
use function Safe\file_get_contents;
use function Safe\json_decode;

class FPM extends AbstractFlowRunHandler
{
    public function run(): int
    {
        $flowRunRequest = $this->getFlowRunRequest();

        return $this->execute($flowRunRequest);
    }

    private function getFlowRunRequest(): FlowRunRequest
    {
        $json = file_get_contents('php://input');
        if (!\is_string($json)) {
            throw new \Exception('Unable to get input content');
        } else {
            $values = json_decode($json, true);
            //            var_dump($json);
            //            var_dump($values);

            $flowId = $values['flowId'];
            $arguments = $values['arguments'];

            $flowRunId = $values['flowRunId'];

            return new FlowRunRequest($flowId, $arguments, $flowRunId);
        }
    }
}
