<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Cloudflare;

use Junges\CloudflareMail\Exceptions\CloudflareTransportException;

final readonly class Config
{
    public function __construct(
        public string $accountId,
        public string $apiToken,
        public string $baseUrl,
        public int $timeout,
    ) {}

    /** @param  array<string, mixed>  $config */
    public static function fromArray(array $config): self
    {
        $accountId = self::stringValue($config, 'account_id');

        if (blank($accountId)) {
            throw CloudflareTransportException::configurationMissing('account_id');
        }

        $apiToken = self::stringValue($config, 'api_token');

        if (blank($apiToken)) {
            throw CloudflareTransportException::configurationMissing('api_token');
        }

        $baseUrl = self::stringValue($config, 'base_url') ?: 'https://api.cloudflare.com/client/v4';
        $timeout = self::intValue($config, 'timeout') ?: 10;

        return new self(
            accountId: $accountId,
            apiToken: $apiToken,
            baseUrl: $baseUrl,
            timeout: $timeout,
        );
    }

    /** @param  array<string, mixed>  $config */
    private static function stringValue(array $config, string $key): string
    {
        $value = $config[$key] ?? null;

        return is_string($value) ? mb_trim($value) : '';
    }

    /** @param  array<string, mixed>  $config */
    private static function intValue(array $config, string $key): int
    {
        $value = $config[$key] ?? null;

        return is_int($value) ? $value : 0;
    }
}
