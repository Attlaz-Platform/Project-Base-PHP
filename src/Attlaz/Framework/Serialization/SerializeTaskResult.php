<?php
declare(strict_types=1);

namespace Attlaz\Framework\Serialization;

use Attlaz\Framework\Model\TaskResult;
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
