<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Exceptions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Junges\CloudflareMail\Contracts\CloudflareTypes;
use RuntimeException;
use Throwable;

/**
 * @phpstan-import-type CloudflareResponseBody from CloudflareTypes
 */
final class CloudflareTransportException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $cloudflareCode = 0,
        public readonly ?int $httpStatus = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /** @param  CloudflareResponseBody  $body */
    public static function fromResponse(Response $response, array $body): self
    {
        $defaultMessage = sprintf('Cloudflare Email Service returned HTTP %d.', $response->status());

        $code = data_get($body, 'errors.0.code', 0);
        $code = is_int($code) ? $code : 0;

        $message = data_get($body, 'errors.0.message');
        $message = is_string($message) && filled($message) ? $message : $defaultMessage;

        return new self(
            sprintf('Unable to send email: %s (code %d).', $message, $code),
            cloudflareCode: $code,
            httpStatus: $response->status(),
        );
    }

    /** @param  list<string>  $bounces */
    public static function fromBounces(Response $response, array $bounces): self
    {
        return new self(
            sprintf('Cloudflare reported permanent bounces for: %s.', implode(', ', $bounces)),
            httpStatus: $response->status(),
        );
    }

    public static function fromConnectionFailure(ConnectionException $e): self
    {
        return new self(
            sprintf('Could not reach the Cloudflare Email Sending API: %s', $e->getMessage()),
            previous: $e,
        );
    }

    public static function inlineAttachmentsNotSupported(): self
    {
        return new self(
            'Cloudflare Email Sending does not support inline attachments.',
        );
    }

    public static function configurationMissing(string $key): self
    {
        return new self(
            sprintf('You are missing a configuration for [%s].', $key)
        );
    }
}
