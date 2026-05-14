# Installation

## Requirements

PHP 8.4 or newer. Laravel 12.0 or newer. A Cloudflare account with a verified sender domain and an API token with the email sending permission.

## Install via Composer

```bash
composer require mateusjunges/laravel-cloudflare-mail
```

This pulls in the package along with its dependencies (`illuminate/http`, `illuminate/mail`, `illuminate/support`, `symfony/mailer`).

## Service provider

The package ships with auto discovery. Laravel registers `Junges\CloudflareMail\CloudflareMailServiceProvider` automatically when the package is installed. You should not need to add anything to `bootstrap/providers.php`.

If your project opts out of auto discovery (via `extra.laravel.dont-discover`), add the provider manually:

```php
// bootstrap/providers.php
return [
    // ...
    Junges\CloudflareMail\CloudflareMailServiceProvider::class,
];
```

## Installing from a local path

If you keep the package in your monorepo (for example, under `packages/laravel-cloudflare-mail`) and have not published it to Packagist yet, configure a path repository in your root `composer.json`:

```json
{
    "require": {
        "mateusjunges/laravel-cloudflare-mail": "@dev"
    },
    "repositories": {
        "cloudflare-mail": {
            "type": "path",
            "url": "packages/laravel-cloudflare-mail",
            "options": {
                "symlink": true
            }
        }
    }
}
```

Then run `composer update mateusjunges/laravel-cloudflare-mail`. Composer creates a symlink under `vendor/mateusjunges/laravel-cloudflare-mail` pointing back at your package directory, so edits to the source are picked up immediately.

The `@dev` constraint is required because path packages report a `dev-<branch>` version that your root `minimum-stability: stable` would otherwise reject.

## Next step

Continue with [Configuration](configuration.md) to wire up your Cloudflare account.
