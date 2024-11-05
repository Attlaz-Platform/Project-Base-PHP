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
    private LoggerInterface|null $logger = null;

    private array $profiles = [];

    private array|null $currentProfile = null;


    public function __construct()
    {

    }

    public function start(string $key, string $label = ''): void
    {
        $this->startProfile($key, $label);
    }

    public function finish(string $key, string $label = ''): void
    {
        $this->endProfile($key);
    }

    public function startProfile(string $key, string $label = ''): void
    {
        if (empty($label)) {
            $label = $key;
        }
        $fullKey = $this->currentProfile === null ? $key : $this->currentProfile['path'] . '::' . $key;

        if (isset($this->profiles[$fullKey])) {
            $this->logger->warning('Unable to start profile `' . $fullKey . '`: already started');
        }
        $profile = [
            'path' => $this->currentProfile === null ? '' : $this->currentProfile['path'] . '::' . $this->currentProfile['key'],

            'key' => $key,
            'label' => $label,
            'start' => microtime(true),
            'end' => null,
            'ellapse' => null,
            'elapse readable' => null,
        ];
        $this->profiles[$profile['path'] . '::' . $key] = $profile;
        $this->currentProfile = $profile;
    }

    public function endProfile(string $key): void
    {
        $end = microtime(true);

        // Shouldn't this always be the case?
        if ($key === $this->currentProfile['key']) {
            $fullKey = $this->currentProfile['path'] . '::' . $key;
        } else {
            echo 'Wrong' . PHP_EOL;
            $fullKey = $this->currentProfile === null ? $key : $this->currentProfile['path'] . '::' . $key;
        }

        if (!isset($this->profiles[$fullKey])) {

            echo 'Unable to find ' . $fullKey . PHP_EOL;
            var_dump(array_keys($this->profiles));
            //$this->logger->warning('Unable to end profile `' . $key . '`: not found (make sure it is started)');
            return;
        }
        $profile = $this->profiles[$fullKey];
        $profile['end'] = $end;

        $elapsedSeconds = $profile['end'] - $profile['start'];
        $profile['elapse'] = $elapsedSeconds;
        $profile['elapse readable'] = Time::readableSeconds($elapsedSeconds);

        $this->profiles[$fullKey] = $profile;

        $x = $this->currentProfile['path'] === '' ? null : $this->currentProfile['path'];

        $currentProfileParent = $x === null ? null : $this->profiles[$x];
        $this->currentProfile = $currentProfileParent;
    }

    public function getProfile(string $key): array
    {
        if (!isset($this->profiles[$key])) {
            $this->logger->warning('Unable to get profile `' . $key . '`: not found (make sure it is started)');
        }
        return $this->profiles[$key];
    }

    public function getProfiles(): array
    {
        return $this->profiles;
    }

    public function debug(): array
    {
        $profiles = $this->profiles;
        $output = [];
        foreach ($profiles as $k => $x) {
            $output[] = str_repeat('    ', count(explode('::', $x['path']))) . ' ' . $x['label'] . ' ' . $x['elapse readable'];
        }
        return $output;
    }
}
