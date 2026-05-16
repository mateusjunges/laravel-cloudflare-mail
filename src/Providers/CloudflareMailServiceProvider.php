<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Providers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Junges\CloudflareMail\Cloudflare\Client;
use Junges\CloudflareMail\Cloudflare\Config as CloudflareConfig;
use Junges\CloudflareMail\Cloudflare\PayloadBuilder;
use Junges\CloudflareMail\Transport\CloudflareTransport;

final class CloudflareMailServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Mail::extend('cloudflare', function (array $mailerConfig = []): CloudflareTransport {
            $config = CloudflareConfig::fromArray([
                'account_id' => $mailerConfig['account_id'] ?? Config::get('services.cloudflare.account_id'),
                'api_token' => $mailerConfig['api_token'] ?? Config::get('services.cloudflare.api_token'),
                'base_url' => $mailerConfig['base_url'] ?? Config::get('services.cloudflare.base_url'),
                'timeout' => $mailerConfig['timeout'] ?? Config::get('services.cloudflare.timeout'),
            ]);

            return new CloudflareTransport(
                client: new Client(
                    accountId: $config->accountId,
                    apiToken: $config->apiToken,
                    baseUrl: $config->baseUrl,
                    timeout: $config->timeout,
                ),
                payloadBuilder: new PayloadBuilder(),
            );
        });
    }
}
