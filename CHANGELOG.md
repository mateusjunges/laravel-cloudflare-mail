# Changelog

All notable changes to `laravel-cloudflare-mail` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-05-21

First stable release. From this version on, the public API (the `cloudflare` mailer configuration keys and the `CloudflareTransportException` class) follows Semantic Versioning. The package's other classes are marked `@internal` and may change in any release.

### Added

- Cloudflare Email Service mail transport for Laravel, registered as the `cloudflare` mailer.
- Credentials resolved from `config/services.php` (`services.cloudflare`) with per mailer overrides on the `config/mail.php` block.
- Configurable API endpoint via `base_url` (default `https://api.cloudflare.com/client/v4`).
- Configurable HTTP request timeout via `timeout` (default 10 seconds).
- Custom header forwarding, with reserved headers filtered out of the payload.
- Attachment support, encoded as base64 in the API payload.
- Synchronous permanent bounce detection: a non empty `result.permanent_bounces` array on an HTTP 200 response is surfaced as a failure.
- Typed failures through `CloudflareTransportException`, exposing `cloudflareCode` and `httpStatus`, rewrapped as a Symfony `TransportException` so they integrate with Laravel queue retries.

### Requirements

- PHP 8.4 or newer.
- Laravel 12 or 13.

[Unreleased]: https://github.com/mateusjunges/laravel-cloudflare-mail/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/mateusjunges/laravel-cloudflare-mail/releases/tag/v1.0.0
