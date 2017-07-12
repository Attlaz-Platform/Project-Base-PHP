<?php
declare(strict_types=1);

namespace Attlaz\Project\Model\Log;

class Processor
{

    private $executionId;

    public function setExecutionId(string $executionId)
    {
        $this->executionId = $executionId;
    }

    public function __invoke(array $record): array
    {
        $record['extra']['execution'] = $this->executionId;

        return $record;
    }
}