<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Cloudflare;

use Junges\CloudflareMail\Exceptions\CloudflareTransportException;

final readonly class Config
{
    public function __construct(
        public string $accountId,
        public string $apiToken,
        public string $baseUrl,
    ) {}

    /** @param  array<string, mixed>  $config */
    public static function fromArray(array $config): self
    {
        $accountId = mb_trim((string) ($config['account_id'] ?? ''));

        if (blank($accountId)) {
            throw CloudflareTransportException::configurationMissing('account_id');
        }

        $apiToken = mb_trim((string) ($config['api_token'] ?? ''));

        if (blank($apiToken)) {
            throw CloudflareTransportException::configurationMissing('api_token');
        }

        $baseUrl = mb_trim((string) ($config['base_url'] ?? 'https://api.cloudflare.com/client/v4'));

        return new self(
            accountId: $accountId,
            apiToken: $apiToken,
            baseUrl: $baseUrl,
        );
    }
}
