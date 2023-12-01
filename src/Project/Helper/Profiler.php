<?php

declare(strict_types=1);

namespace Attlaz\Project\Helper;

use Echron\Tools\Time;
use Psr\Log\LoggerInterface;

class Profiler
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    private array $profiles = [];


    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function startProfile(string $key, string $label = ''): void
    {
        if (empty($label)) {
            $label = $key;
        }
        if (isset($this->profiles[$key])) {
            $this->logger->warning('Unable to start profile `' . $key . '`: already started');
        }
        $this->profiles[$key] = [
            'key'   => $key,
            'label' => $label,
            'start' => microtime(true),
            'end'   => null,
        ];
    }

    public function endProfile(string $key): void
    {
        $end = microtime(true);
        if (!isset($this->profiles[$key])) {
            $this->logger->warning('Unable to end profile `' . $key . '`: not found (make sure it is started)');
        }
        $this->profiles[$key]['end'] = $end;

        $elapsedSeconds = $this->profiles[$key]['end'] - $this->profiles[$key]['start'];
        $this->profiles[$key]['elapse'] = $elapsedSeconds;
        $this->profiles[$key]['elapse readable'] = Time::readableSeconds($elapsedSeconds);
    }

    public function getProfile(string $key): array
    {
        if (!isset($this->profiles[$key])) {
            $this->logger->warning('Unable to get profile `' . $key . '`: not found (make sure it is started)');
        }
        return $this->profiles[$key];
    }

}
