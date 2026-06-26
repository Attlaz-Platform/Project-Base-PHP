#!/usr/bin/env php
<?php
declare(strict_types=1);
// Fast in-container / worker entry point: boot the app directly. No version checks, no Docker.
// The Attlaz worker calls this, and bin/console runs it inside the container.
require __DIR__ . '/../vendor/autoload.php';
new \Attlaz\Project\Project(\dirname(__DIR__))->run();
