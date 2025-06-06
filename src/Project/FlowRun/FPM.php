<?php

declare(strict_types=1);

namespace Attlaz\Project\FlowRun;

use Attlaz\Project\Model\FlowRunRequest;

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
        }

        $values = json_decode($json, true);
        //            var_dump($json);
        //            var_dump($values);

        $flowId = $values['flowId'];
        $arguments = $values['arguments'];

        $flowRunId = $values['flowRunId'];

        $request = new FlowRunRequest($flowId, $arguments);
        $request->flowRunId = $flowRunId;
        return $request;
    }
}
