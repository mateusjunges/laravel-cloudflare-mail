# Installation

## Requirements

PHP 8.4 or newer. Laravel 12.0 or newer. A Cloudflare account with a verified sender domain and an API token with the email sending permission.

## Install via Composer

```bash
composer require mateusjunges/laravel-cloudflare-mail
```

This pulls in the package along with its dependencies (`illuminate/http`, `illuminate/mail`, `illuminate/support`, `symfony/mailer`).

## Service provider

The package ships with auto discovery. Laravel registers `Junges\CloudflareMail\Providers\CloudflareMailServiceProvider` automatically when the package is installed. You should not need to add anything to `bootstrap/providers.php`.

If your project opts out of auto discovery (via `extra.laravel.dont-discover`), add the provider manually:

```php
// bootstrap/providers.php
return [
    // ...
    Junges\CloudflareMail\Providers\CloudflareMailServiceProvider::class,
];
```

## Next step

Continue with [Configuration](configuration.md) to wire up your Cloudflare account.
