# Error handling

The driver maps Cloudflare's error responses to two exception types. Code inside the package throws `Junges\CloudflareMail\Exceptions\CloudflareTransportException`. The transport rewraps that as `Symfony\Component\Mailer\Exception\TransportException` before it bubbles out, so it integrates with Laravel's queue retries and any custom mail event listeners.

## Exception types

### `CloudflareTransportException`

Thrown by the HTTP client when Cloudflare returns an unsuccessful response, when the response shape indicates `success: false`, when the response contains permanent bounces, or when the HTTP call cannot reach Cloudflare at all. Construct instances via the named factories rather than the constructor:

```php
final class CloudflareTransportException extends RuntimeException
{
    public readonly int $cloudflareCode;
    public readonly ?int $httpStatus;

    public static function fromResponse(Response $response, array $body): self;
    public static function fromBounces(Response $response, array $bounces): self;
    public static function fromConnectionFailure(ConnectionException $e): self;
}
```

The `cloudflareCode` property holds the numeric Cloudflare error code (for example, `10004` for throttling). The `httpStatus` property holds the HTTP status returned by the API. Both are zero or null if the failure happened before a response was received (such as a DNS or TLS error).

### `TransportException`

Thrown by `Junges\CloudflareMail\Transport\CloudflareTransport::doSend()` when the underlying client fails. It wraps the original `CloudflareTransportException` as the `previous` cause, so you can still inspect the Cloudflare error code if you need to:

```php
try {
    Mail::to($user)->send(new OrderShipped($order));
} catch (\Symfony\Component\Mailer\Exception\TransportException $e) {
    $cloudflare = $e->getPrevious();

    if ($cloudflare instanceof \Junges\CloudflareMail\Exceptions\CloudflareTransportException
        && $cloudflare->cloudflareCode === 10004) {
        // Cloudflare throttled the send. Schedule a retry.
    }
}
```

## Cloudflare error codes

The codes below are the ones documented in the public Cloudflare Email Service reference. New codes may appear over time; refer to the [official API documentation](https://developers.cloudflare.com/email-service/api/send-emails/) for the authoritative list.

| HTTP status | Cloudflare code | Meaning |
|---|---|---|
| 400 | 10001 | The request body did not match the expected schema. |
| 400 | 10200 | The `to`, `cc`, `bcc`, or `from` address was rejected as invalid. |
| 400 | 10202 | The message exceeded the 5 MiB total size limit. |
| 403 | 10203 | Email sending is disabled for the account, or the token lacks the email permission. |
| 429 | 10004 | The send was throttled. |
| 500 | 10002 | Cloudflare's internal error. |

## Synchronous bounces

Cloudflare returns information about which recipients failed delivery inside the `result.permanent_bounces` array of a successful (`HTTP 200`, `success: true`) response. The driver treats a non empty `permanent_bounces` list as a failure: it throws a `CloudflareTransportException` with the bounced addresses in the message. This means a partially failed multi recipient send raises an exception even though Cloudflare reported HTTP 200.

If you would prefer the partial success to succeed silently, catch the exception in your calling code and inspect it before deciding what to do.

## Queue retries

When a Laravel queue worker processes a mailable that implements `ShouldQueue`, an exception thrown during send marks the job as failed and the worker reschedules it according to your queue configuration (`tries`, `backoff`, `retryUntil`). Cloudflare's `10004` (throttled) and `10002` (internal) codes are the typical transient failures that benefit from a retry.

The driver does not honor a `Retry-After` header from Cloudflare at this time, because the public API documentation does not yet describe one. If your retry strategy needs a backoff that follows the server's advice, you can read the header from the wrapped `CloudflareTransportException` and re queue the job manually.

## Connectivity failures

If the API cannot be reached at all (DNS lookup failure, TLS handshake error, connection timeout), the underlying `Illuminate\Http\Client\ConnectionException` is wrapped in a `CloudflareTransportException` whose `cloudflareCode` is `0` and `httpStatus` is `null`. The original exception is available via `getPrevious()` if you need it.

## Logging

If you want to log every failed send, hook into Laravel's `MessageSending` and `MessageSent` events, or wrap your `Mail::send` calls in a try/catch that funnels into your logger. The driver does not log on its own; it only throws.

## Common setup mistakes

A 10203 ("sending disabled") response usually means one of three things: the sender domain has not been verified in your Cloudflare account; the API token does not have the email permission; or the account is not enrolled in the Email Service. Check the Cloudflare dashboard before assuming the driver is misbehaving.

A 10200 ("invalid email") response is almost always a malformed `from` or `to` address. Double check `MAIL_FROM_ADDRESS` and any per mailable overrides.

A 10202 ("too big") response is the 5 MiB total size limit, including base64 encoded attachments. Bear in mind that base64 inflates binary content by roughly a third, so a 4 MiB PDF becomes about 5.3 MiB on the wire.
