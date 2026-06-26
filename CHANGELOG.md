# Changelog

All notable changes to `attlaz/project-base` are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Releases prior to 1.18.0 are tracked via git tags only.

## [1.18.0] - 2026-06-26

### Changed

- **`RunFlow` decodes flow arguments with `JSON_THROW_ON_ERROR`** and catches `\JsonException`,
  making the previously no-op try/catch meaningful — malformed argument JSON now reports a clear
  "Unable to decode arguments" error instead of falling through to a generic check.
- **`Environment` class constants are now typed** (`const string …`), matching the typed-constant
  style used elsewhere.
- **`array_find()` replaces the search-only command-lookup `foreach`** in `RunFlowInteractive`.

[1.18.0]: https://bitbucket.org/attlaz/project-base/branches/compare/1.18.0%0D1.17.0
