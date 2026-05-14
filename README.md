# Laravel Cloudflare Mail

A Laravel mail driver for [Cloudflare Email Service](https://developers.cloudflare.com/email-service/), the outbound email API on Cloudflare's network. Once installed and configured, the driver plugs into Laravel's mail abstraction. Setting `MAIL_MAILER=cloudflare` is enough to route every `Mail::send`, queued mailable, and notification through Cloudflare.

## Requirements

PHP 8.4 or newer. Laravel 12.0 or newer. A Cloudflare account with a verified sender domain and an API token that has the email sending permission.

## Installation

```bash
composer require mateusjunges/laravel-cloudflare-mail
```

The service provider auto registers via Laravel's package discovery. No manual registration needed.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.
