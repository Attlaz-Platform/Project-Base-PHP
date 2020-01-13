<?php

declare(strict_types=1);

namespace Attlaz\Project\Helper;

use Psr\Log\LoggerInterface;

class OutputHelper
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    private $lastProgressOutput = [];

    private const LIMIT_PROGRESS_SECONDS = 2.5;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function progress(string $key, int $current, int $total, string $label): void
    {
        if ($this->shouldOutput($key)) {
            $procent = \number_format($current / $total * 100, 2);
            $this->logger->info($label . ' ' . $current . '/' . $total . ' ' . $procent . '%');

            $this->markOutput($key);
        }
    }

    private function shouldOutput(string $key): bool
    {
        if (!isset($this->lastProgressOutput[$key])) {
            return true;
        }

        $lastOutputSeconds = \microtime(true) - $this->lastProgressOutput[$key];

        if ($lastOutputSeconds > self::LIMIT_PROGRESS_SECONDS) {
            return true;
        }

        return false;
    }

    private function markOutput(string $key): void
    {
        $this->lastProgressOutput[$key] = \microtime(true);
    }
}
