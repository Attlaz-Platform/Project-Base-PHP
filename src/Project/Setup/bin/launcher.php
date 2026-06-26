<?php

declare(strict_types=1);

/*
 * Shared helpers for the bin/ launchers (console, composer, ...).
 *
 * Kept dependency-free and inside bin/ -- NOT in vendor/ -- so it still works when vendor/ is
 * absent (bin/composer must run for `composer install`), and parses on whatever host PHP exists
 * before any Docker delegation. Use only widely-compatible syntax here.
 */

/**
 * The PHP major/minor this project requires, parsed from composer.json "require.php"
 * (any constraint format: "^8.4", ">=8.4", "^8.4.0", ...).
 *
 * @return array{0: int, 1: int}|null [major, minor], or null when it can't be determined
 */
function attlaz_required_php(string $projectRoot): ?array
{
    $raw = @file_get_contents($projectRoot . '/composer.json');
    if ($raw === false) {
        return null;
    }
    $composer = \json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    $constraint = is_array($composer) ? ($composer['require']['php'] ?? '') : '';
    if (is_string($constraint) && preg_match('/(\d+)\.(\d+)/', $constraint, $m)) {
        return [(int)$m[1], (int)$m[2]];
    }
    return null;
}

/** The attlaz/php image tag for a [major, minor] pair returned by attlaz_required_php(). */
function attlaz_php_image(array $version): string
{
    return 'attlaz/php:' . $version[0] . '.' . $version[1];
}

/**
 * Run a command inside the given image with the project mounted at /var/attlaz; returns the
 * command's exit code. Allocates a TTY only when attached to one, so piped use still works.
 */
function attlaz_run_in_docker(string $projectRoot, string $image, string $command, array $args): int
{
    $tty = stream_isatty(STDIN) ? '-it' : '-i';
    $argString = implode(' ', array_map('\escapeshellarg', $args));
    passthru(
        'docker run --rm ' . $tty . ' --init -v ' . escapeshellarg($projectRoot . ':/var/attlaz')
        . ' -w /var/attlaz ' . escapeshellarg($image) . ' ' . $command . ' ' . $argString,
        $exitCode
    );

    return (int)$exitCode;
}
