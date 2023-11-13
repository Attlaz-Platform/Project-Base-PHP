<?php

declare(strict_types=1);

namespace Attlaz\Project\Serialization;

use Attlaz\Project\Model\FlowRunResult;

class SerializeFlowRunResult
{
    public function __invoke(FlowRunResult $flowRunResult): string
    {
        $serialized = json_encode($flowRunResult, JSON_THROW_ON_ERROR);

        if (!\is_string($serialized)) {
            throw new \Exception('Unable to encode flow run result');
        }

        return $serialized;
    }
}
