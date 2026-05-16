<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Tests\Unit;

use Illuminate\Support\Facades\Http;
use Junges\CloudflareMail\Cloudflare\Client;
use Junges\CloudflareMail\Cloudflare\PayloadBuilder;
use Junges\CloudflareMail\Transport\CloudflareTransport;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

function transport(): CloudflareTransport
{
    return new CloudflareTransport(
        client: new Client('acct', 'tok', 'https://api.cloudflare.com/client/v4', 10),
        payloadBuilder: new PayloadBuilder(),
    );
}

it('delegates a happy-path send to the API client', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => ['delivered' => ['rcpt@example.com'], 'permanent_bounces' => [], 'queued' => []],
        ], 200),
    ]);

    $email = new Email()
        ->from('sender@example.com')
        ->to('rcpt@example.com')
        ->subject('Hi')
        ->text('Body');

    transport()->send($email);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => $request['subject'] === 'Hi' && $request['text'] === 'Body');
});

it('rethrows API failures as TransportException', function (): void {
    Http::fake([
        'api.cloudflare.com/*' => Http::response([
            'success' => false,
            'errors' => [['code' => 10200, 'message' => 'invalid email']],
        ], 400),
    ]);

    $email = new Email()
        ->from('sender@example.com')
        ->to('bad@example')
        ->subject('s')
        ->text('t');

    expect(fn () => transport()->send($email))->toThrow(TransportException::class, 'invalid email');
});

it('rethrows an inline attachment rejection as TransportException', function (): void {
    Http::fake();

    $email = new Email()
        ->from('sender@example.com')
        ->to('rcpt@example.com')
        ->subject('s')
        ->html('<img src="cid:logo">')
        ->addPart(new DataPart('img-bytes', 'logo.png', 'image/png')->asInline());

    expect(fn () => transport()->send($email))
        ->toThrow(TransportException::class, 'does not support inline attachments');

    Http::assertNothingSent();
});

it('returns a stable string identifier', function (): void {
    expect((string) transport())->toBe('cloudflare');
});
