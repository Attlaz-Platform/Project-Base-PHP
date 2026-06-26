# Changelog

All notable changes to `attlaz/project-base` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Releases prior to 1.18.0 are tracked via git tags only.

## [1.18.3] - 2026-06-26

Reworks the `bin/` launchers (templated into projects via `src/Project/Setup/bin/`) so that
`bin/console` / `bin/composer` "just work" on a host whose PHP is older than the project requires,
by transparently running inside the matching `attlaz/php` Docker image.

### Added

- **`bin/console.php`** — the fast in-container / worker entry point. Boots the app directly with
  no version checks and no Docker; this is what the Attlaz worker invokes (it has no Docker access).
- **`bin/composer`** — runs Composer inside the project's `attlaz/php:<version>` image, so platform
  requirements resolve against the target runtime. The host needs only Docker, not Composer or the
  matching PHP.
- **`bin/launcher.php`** — a small, dependency-free helper (PHP-version detection from
  `composer.json` + `docker run`) shared by the `bin/` launchers. Kept in `bin/` (not `vendor/`) so
  it works even when `vendor/` is absent (e.g. `bin/composer install`).

### Changed

- **`bin/console` is now a thin developer launcher.** When the host PHP is older than
  `composer.json`'s `require.php`, it re-runs the command inside the matching `attlaz/php:<version>`
  image (executing `bin/console.php`); otherwise it boots directly. It is intentionally kept
  parseable on old PHP so the routing runs before any newer-PHP code is loaded.
- **Docker-image detection (`bin/py/lib/utils.py`) is no longer a maintained version list.** It
  derives `attlaz/php:<major.minor>` from any constraint format via a regex (was a brittle
  exact-string `match`), lets `docker run` pull the image on demand, and reports a clear error when
  the image doesn't exist (Docker exit `125`) instead of silently using a fallback.

### Fixed

- **`bin/console.sh` pointed at a non-existent path** (`bin/console.py` instead of
  `bin/py/console.py`), so the unix launcher silently failed and fell back to the host PHP.

## [1.18.0] - 2026-06-26

### Changed

- **`RunFlow` decodes flow arguments with `JSON_THROW_ON_ERROR`** and catches `\JsonException`,
  making the previously no-op try/catch meaningful — malformed argument JSON now reports a clear
  "Unable to decode arguments" error instead of falling through to a generic check.
- **`Environment` class constants are now typed** (`const string …`), matching the typed-constant
  style used elsewhere.
- **`array_find()` replaces the search-only command-lookup `foreach`** in `RunFlowInteractive`.
- **Symfony Console/YAML 8 support.** Now requires `symfony/console` and `symfony/yaml` `^8.1`
  (and therefore PHP 8.4). The CLI bootstrap uses `Application::addCommand()` instead of the
  `Application::add()` removed in Symfony 8.0, and `#[\Override]` was added to the console
  command classes' `execute()` / `configure()` methods.
- **Clearer startup / configuration errors.** `Environment::init()` now throws actionable errors
  when `project_environment` is missing/empty or the environment can't be resolved, instead of a
  null-property read that produced a cryptic `TypeError`. The `Project` bootstrap now catches
  `\Throwable` (not just `\Exception`, which let `Error`s slip through) and wraps failures as
  "Unable to start project: …", preserving the original as the cause.

[1.18.3]: https://bitbucket.org/attlaz/project-base/branches/compare/1.18.3%0D1.18.2
[1.18.0]: https://bitbucket.org/attlaz/project-base/branches/compare/1.18.0%0D1.17.0
