# Configuration

The driver expects three things. A pair of credentials in your environment, a mailer block in `config/mail.php`, and a services block in `config/services.php`.

## Environment variables

Add the credentials to your `.env` file:

```env
CLOUDFLARE_EMAIL_ACCOUNT_ID=your-cloudflare-account-id
CLOUDFLARE_EMAIL_API_TOKEN=your-cloudflare-api-token
```

The account ID is visible in the Cloudflare dashboard URL when you open your account. The API token must be scoped with the email sending permission. Create it from the Cloudflare dashboard under "My Profile" then "API Tokens".

To make the new driver the default mailer for the application, set:

```env
MAIL_MAILER=cloudflare
```

You can also leave `MAIL_MAILER` pointed at another driver and explicitly route messages through Cloudflare via `Mail::mailer('cloudflare')`, which is useful during a gradual rollout.

## `config/mail.php`

Register the mailer alongside the other transports. The credentials are read from `config/services.php`, so the block here only needs the transport name.

```php
'mailers' => [
    // ...
    'cloudflare' => [
        'transport' => 'cloudflare',
    ],
],
```

## `config/services.php`

Add the credentials block. The keys here mirror the environment variable names.

```php
'cloudflare_email' => [
    'account_id' => env('CLOUDFLARE_EMAIL_ACCOUNT_ID'),
    'api_token' => env('CLOUDFLARE_EMAIL_API_TOKEN'),
],
```

The service provider will throw a `RuntimeException` at resolution time if either value is empty, so a misconfigured environment fails loudly the first time you try to send an email through the driver.

## Inline overrides

If you prefer keeping credentials inside the mailer block itself (for instance, to scope different mailers to different Cloudflare accounts), pass them directly:

```php
'mailers' => [
    'cloudflare' => [
        'transport' => 'cloudflare',
        'account_id' => env('CLOUDFLARE_EMAIL_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_EMAIL_API_TOKEN'),
    ],
],
```

The driver prefers values from the mailer block when present, and falls back to `config/services.php` otherwise.

## Cloudflare dashboard setup

Before the driver can deliver mail, you also need to complete two setup steps inside Cloudflare itself.

First, verify your sender domain. Cloudflare will not accept a `from` address whose domain has not been verified in your account. The dashboard guides you through the required DNS records (SPF, DKIM, and a verification TXT record).

Second, confirm that your API token grants the email sending permission. A token created with the wrong scope returns HTTP 403 with Cloudflare error code 10203 ("email.sending.error.email.sending_disabled").

## From address

Laravel uses `MAIL_FROM_ADDRESS` and `MAIL_FROM_NAME` as the default sender for outgoing mail. Make sure `MAIL_FROM_ADDRESS` uses a domain you have verified in Cloudflare. Any individual mailable can override this via `Mail::to(...)->from(...)` or the mailable's own `envelope()` method.

## Next step

Continue with [Usage](usage.md) for examples of sending mail through the driver.
