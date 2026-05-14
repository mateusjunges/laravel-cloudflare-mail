<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Exceptions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Junges\CloudflareMail\Cloudflare\CloudflareTypes;
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
        $code = (int) data_get($body, 'errors.0.code', 0);
        $message = (string) data_get(
            $body,
            'errors.0.message',
            sprintf('Cloudflare Email Service returned HTTP %d.', $response->status()),
        );

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
            sprintf('Could not reach the Cloudflare Email Service API: %s', $e->getMessage()),
            previous: $e,
        );
    }
}
