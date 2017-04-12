<?php
declare(strict_types=1);

namespace Attlaz\Core\Command;

use Attlaz\Core\Model\TaskResult;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

class SerializeTaskResult
{
    public function __invoke(TaskResult $taskResult): string
    {


        $jsonEncoder = new JsonEncoder();

        $serialized = $jsonEncoder->encode($taskResult, 'json');

        return $serialized;

    }
}
