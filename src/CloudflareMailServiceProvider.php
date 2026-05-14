<?php declare(strict_types=1);

namespace Junges\CloudflareMail;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Junges\CloudflareMail\Cloudflare\Client;
use Junges\CloudflareMail\Transport\CloudflareTransport;
use RuntimeException;

final class CloudflareMailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('cloudflare', function (array $mailerConfig = []): CloudflareTransport {
            $accountId = $mailerConfig['account_id'] ?? Config::get('services.cloudflare_email.account_id');
            $apiToken = $mailerConfig['api_token'] ?? Config::get('services.cloudflare_email.api_token');

            throw_if(blank($accountId), new RuntimeException(
                'Cloudflare Email Service account ID is not configured. Set CLOUDFLARE_EMAIL_ACCOUNT_ID in your environment.',
            ));

            throw_if(blank($apiToken), new RuntimeException(
                'Cloudflare Email Service API token is not configured. Set CLOUDFLARE_EMAIL_API_TOKEN in your environment.',
            ));

            return new CloudflareTransport(
                new Client(
                    accountId: (string) $accountId,
                    apiToken: (string) $apiToken,
                    http: $this->app->make(Factory::class),
                ),
            );
        });
    }
}
