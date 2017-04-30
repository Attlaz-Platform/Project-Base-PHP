<?php
declare(strict_types=1);

namespace Attlaz\Project\Serialization;

use Attlaz\Project\Model\TaskResult;
use Symfony\Component\Serializer\Encoder\JsonEncode;

class SerializeTaskResult
{
    public function __invoke(TaskResult $taskResult): string
    {
        $jsonEncoder = new JsonEncode();

        $serialized = $jsonEncoder->encode($taskResult, 'json');

        return $serialized;
    }
}
