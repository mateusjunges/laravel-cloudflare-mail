<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Cloudflare;

use Illuminate\Support\Facades\Config as LaravelConfig;
use Junges\CloudflareMail\Transport\CloudflareTransport;

final readonly class Factory
{
    public function __construct(
        private PayloadBuilder $payloadBuilder,
    ) {}

    /** @param  array<string, mixed>  $config */
    public function make(array $config = []): CloudflareTransport
    {
        $cloudflareConfig = Config::fromArray([
            'account_id' => $config['account_id'] ?? LaravelConfig::get('services.cloudflare.account_id'),
            'api_token' => $config['api_token'] ?? LaravelConfig::get('services.cloudflare.api_token'),
            'base_url' => $config['base_url'] ?? LaravelConfig::get('services.cloudflare.base_url'),
        ]);

        $client = new Client(
            accountId: $cloudflareConfig->accountId,
            apiToken: $cloudflareConfig->apiToken,
            baseUrl: $cloudflareConfig->baseUrl,
        );

        return new CloudflareTransport(
            client: $client,
            payloadBuilder: $this->payloadBuilder,
            config: $cloudflareConfig,
        );
    }
}
