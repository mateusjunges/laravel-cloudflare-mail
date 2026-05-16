<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Tests\Unit;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Junges\CloudflareMail\Cloudflare\Client;
use Junges\CloudflareMail\Exceptions\CloudflareTransportException;

function successBody(array $delivered = ['to@example.com']): array
{
    return [
        'success' => true,
        'errors' => [],
        'messages' => [],
        'result' => [
            'delivered' => $delivered,
            'permanent_bounces' => [],
            'queued' => [],
        ],
    ];
}

it('posts to the account-scoped endpoint with bearer auth and JSON body', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response(successBody(), 200),
    ]);

    new Client('acct-123', 'cf-token', 'https://api.cloudflare.com/client/v4', 10)->send([
        'from' => 'sender@example.com',
        'to' => ['to@example.com'],
        'subject' => 'Hi',
        'text' => 'Body',
    ]);

    Http::assertSent(fn ($request) => $request->method() === 'POST'
        && $request->url() === 'https://api.cloudflare.com/client/v4/accounts/acct-123/email/sending/send'
        && $request->header('Authorization')[0] === 'Bearer cf-token'
        && $request->header('Content-Type')[0] === 'application/json'
        && $request->header('Accept')[0] === 'application/json'
        && $request['from'] === 'sender@example.com'
        && $request['to'] === ['to@example.com']);
});

it('throws with the Cloudflare error code and message on a 4xx response', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => false,
            'errors' => [['code' => 10200, 'message' => 'email.sending.error.email.invalid']],
            'messages' => [],
            'result' => null,
        ], 400),
    ]);

    $client = new Client('acct', 'tok', 'https://api.cloudflare.com/client/v4', 10);

    expect(fn () => $client->send(['from' => 'a@b.c', 'to' => ['x@y'], 'subject' => 's', 'text' => 't']))
        ->toThrow(CloudflareTransportException::class, 'email.sending.error.email.invalid');
});

it('exposes the Cloudflare code and http status on the exception', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => false,
            'errors' => [['code' => 10004, 'message' => 'email.sending.error.throttled']],
            'messages' => [],
            'result' => null,
        ], 429),
    ]);

    $client = new Client('acct', 'tok', 'https://api.cloudflare.com/client/v4', 10);

    try {
        $client->send(['from' => 'a@b.c', 'to' => ['x@y'], 'subject' => 's', 'text' => 't']);
        $this->fail('Expected CloudflareTransportException.');
    } catch (CloudflareTransportException $e) {
        expect($e->cloudflareCode)->toBe(10004);
        expect($e->httpStatus)->toBe(429);
    }
});

it('treats permanent_bounces in a 200 response as a failure', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [
                'delivered' => [],
                'permanent_bounces' => ['bounced@example.com'],
                'queued' => [],
            ],
        ], 200),
    ]);

    $client = new Client('acct', 'tok', 'https://api.cloudflare.com/client/v4', 10);

    expect(fn () => $client->send(['from' => 'a@b.c', 'to' => ['bounced@example.com'], 'subject' => 's', 'text' => 't']))
        ->toThrow(CloudflareTransportException::class, 'bounced@example.com');
});

it('falls back to a generic message when the API returns success false without an error message', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => false,
            'errors' => [],
            'messages' => [],
            'result' => null,
        ], 502),
    ]);

    $client = new Client('acct', 'tok', 'https://api.cloudflare.com/client/v4', 10);

    expect(fn () => $client->send(['from' => 'a@b.c', 'to' => ['x@y'], 'subject' => 's', 'text' => 't']))
        ->toThrow(CloudflareTransportException::class, 'Cloudflare Email Service returned HTTP 502.');
});

it('wraps a connection failure in a CloudflareTransportException', function (): void {
    Http::fake(function (): never {
        throw new ConnectionException('DNS lookup failed');
    });

    $client = new Client('acct', 'tok', 'https://api.cloudflare.com/client/v4', 10);

    expect(fn () => $client->send(['from' => 'a@b.c', 'to' => ['x@y'], 'subject' => 's', 'text' => 't']))
        ->toThrow(CloudflareTransportException::class, 'Could not reach the Cloudflare Email Sending API');
});
