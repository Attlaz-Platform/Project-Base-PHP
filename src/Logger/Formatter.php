<?php

declare(strict_types=1);

namespace Attlaz\Project\Logger;

use Attlaz\Project\Exception\RuntimeException;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\NormalizerFormatter;

class Formatter extends NormalizerFormatter implements FormatterInterface
{

    public function format(array $record)
    {
        if (isset($record['context'])) {
            $record['context'] = $this->formatContext($record['context']);
        }

        return $record;
    }

    public function formatBatch(array $records)
    {
        // TODO: Implement formatBatch() method.
        return $records;
    }

    private function formatContext(array $context): array
    {
        $result = [];

        foreach ($context as $key => $value) {
            $result[$key] = $this->normalize($value);
        }

        return $result;
    }

    protected function normalizeException($e)
    {
        $data = parent::normalizeException($e);
        if ($e instanceof RuntimeException) {
            $data['context'] = $e->getContext();
        }

        return $data;
    }
}
