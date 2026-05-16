<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Junges\CloudflareMail\Exceptions\CloudflareTransportException;
use Junges\CloudflareMail\Transport\CloudflareTransport;
use Symfony\Component\Mime\Email;

function fakeAcceptedResponse(): void
{
    Http::fake([
        '*' => Http::response([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => ['delivered' => ['rcpt@example.com'], 'permanent_bounces' => [], 'queued' => []],
        ], 200),
    ]);
}

function sendThroughCloudflare(): void
{
    Mail::mailer('cloudflare')->getSymfonyTransport()->send(
        new Email()
            ->from('sender@example.com')
            ->to('rcpt@example.com')
            ->subject('s')
            ->text('t'),
    );
}

it('resolves the cloudflare mailer to the Cloudflare transport', function (): void {
    config()->set('mail.mailers.cloudflare', [
        'transport' => 'cloudflare',
        'account_id' => 'acct-123',
        'api_token' => 'cf-token',
    ]);

    $transport = Mail::mailer('cloudflare')->getSymfonyTransport();

    expect($transport)->toBeInstanceOf(CloudflareTransport::class);
});

it('throws when resolving without an account_id', function (): void {
    config()->set('mail.mailers.cloudflare', [
        'transport' => 'cloudflare',
        'api_token' => 'cf-token',
    ]);

    expect(fn () => Mail::mailer('cloudflare'))
        ->toThrow(CloudflareTransportException::class, '[account_id]');
});

it('throws when resolving without an api_token', function (): void {
    config()->set('mail.mailers.cloudflare', [
        'transport' => 'cloudflare',
        'account_id' => 'acct-123',
    ]);

    expect(fn () => Mail::mailer('cloudflare'))
        ->toThrow(CloudflareTransportException::class, '[api_token]');
});

it('falls back to services.cloudflare when the mailer block is bare', function (): void {
    config()->set('mail.mailers.cloudflare', ['transport' => 'cloudflare']);
    config()->set('services.cloudflare', [
        'account_id' => 'acct-from-services',
        'api_token' => 'tok-from-services',
    ]);

    fakeAcceptedResponse();
    sendThroughCloudflare();

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/accounts/acct-from-services/')
        && $request->header('Authorization')[0] === 'Bearer tok-from-services');
});

it('prefers credentials from the mailer block over services.cloudflare', function (): void {
    config()->set('mail.mailers.cloudflare', [
        'transport' => 'cloudflare',
        'account_id' => 'acct-from-mailer',
        'api_token' => 'tok-from-mailer',
    ]);
    config()->set('services.cloudflare', [
        'account_id' => 'acct-from-services',
        'api_token' => 'tok-from-services',
    ]);

    fakeAcceptedResponse();
    sendThroughCloudflare();

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/accounts/acct-from-mailer/')
        && $request->header('Authorization')[0] === 'Bearer tok-from-mailer');
});

it('routes requests at the base_url from the mailer config', function (): void {
    config()->set('mail.mailers.cloudflare', [
        'transport' => 'cloudflare',
        'account_id' => 'acct-123',
        'api_token' => 'cf-token',
        'base_url' => 'https://staging.cloudflare.example/v4',
    ]);

    fakeAcceptedResponse();
    sendThroughCloudflare();

    Http::assertSent(fn ($request): bool => str_starts_with(
        (string) $request->url(),
        'https://staging.cloudflare.example/v4/accounts/acct-123/',
    ));
});
