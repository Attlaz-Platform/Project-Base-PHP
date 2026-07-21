# Attlaz Project Base

[![Latest Stable Version](https://img.shields.io/packagist/v/attlaz/project-base.svg)](https://packagist.org/packages/attlaz/project-base)

The runtime foundation for a PHP project on [Attlaz](https://attlaz.com), a cloud-based iPaaS (Integration Platform as a Service). It boots your project and wires up configuration, dependency injection, logging and the Attlaz API client, then runs your commands — both locally and inside the Attlaz platform.

## Requirements

- PHP 8.4+

## Installation

Most projects start from the ready-made skeleton, which is already wired to this package:

```sh
composer create-project attlaz/skeleton my-project
```

To add it to an existing project manually:

```sh
composer require attlaz/project-base
```

## Quick start

`attlaz/project-base` provides the `Attlaz\Project\Project` runtime. A minimal entry point boots and runs it:

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use Attlaz\Project\Project;

(new Project(__DIR__))->run();
```

The [`attlaz/skeleton`](https://packagist.org/packages/attlaz/skeleton) template sets this up for you in `bin/console`, together with a sample command, configuration (`config.yaml`, `.env`) and dependency-injection wiring.

## What's included

- Project bootstrap and console command runner (Symfony Console)
- Configuration loading — YAML files and environment variables
- Dependency injection (PHP-DI)
- Connection pooling
- Logging into Attlaz log streams (via `attlaz/attlaz-monolog`)
- The Attlaz API client (`attlaz/client`)

## Documentation

Full documentation at [docs.attlaz.com](https://docs.attlaz.com).

## Getting help

- [Issue Tracker](https://github.com/Attlaz-Platform/Project-Base-PHP/issues) — questions, bug reports and feature requests
- Developer support: developers@attlaz.com

## License

[MIT](LICENSE)
