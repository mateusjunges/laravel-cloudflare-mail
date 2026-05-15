<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Tests\Unit;

use Junges\CloudflareMail\Cloudflare\PayloadBuilder;
use Junges\CloudflareMail\Exceptions\CloudflareTransportException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

/** @return array<string, mixed> */
function buildPayload(Email $email): array
{
    $email->ensureValidity();
    $envelope = Envelope::create($email);

    return new PayloadBuilder()->build($email, $envelope);
}

it('serializes from, to, subject, text, and html', function (): void {
    $email = new Email()
        ->from(new Address('sender@example.com', 'Sender Name'))
        ->to(new Address('rcpt@example.com', 'Recipient'))
        ->subject('Subject line')
        ->text('Plain body')
        ->html('<p>HTML body</p>');

    $payload = buildPayload($email);

    expect($payload['from'])->toBe('Sender Name <sender@example.com>');
    expect($payload['to'])->toBe(['Recipient <rcpt@example.com>']);
    expect($payload['subject'])->toBe('Subject line');
    expect($payload['text'])->toBe('Plain body');
    expect($payload['html'])->toBe('<p>HTML body</p>');
});

it('formats addresses without a name as bare email', function (): void {
    $email = new Email()
        ->from('plain@example.com')
        ->to('rcpt@example.com')
        ->subject('s')
        ->text('t');

    $payload = buildPayload($email);

    expect($payload['from'])->toBe('plain@example.com');
    expect($payload['to'])->toBe(['rcpt@example.com']);
});

it('serializes cc, bcc, and reply_to', function (): void {
    $email = new Email()
        ->from('sender@example.com')
        ->to('rcpt@example.com')
        ->cc('cc1@example.com', new Address('cc2@example.com', 'Two'))
        ->bcc('bcc@example.com')
        ->replyTo(new Address('reply@example.com', 'Reply'))
        ->subject('s')
        ->text('t');

    $payload = buildPayload($email);

    expect($payload['cc'])->toBe(['cc1@example.com', 'Two <cc2@example.com>']);
    expect($payload['bcc'])->toBe(['bcc@example.com']);
    expect($payload['reply_to'])->toBe('Reply <reply@example.com>');
});

it('omits cc, bcc, reply_to, html, headers, and attachments when empty', function (): void {
    $email = new Email()
        ->from('sender@example.com')
        ->to('rcpt@example.com')
        ->subject('s')
        ->text('t');

    $payload = buildPayload($email);

    expect($payload)->not->toHaveKeys(['cc', 'bcc', 'reply_to', 'html', 'headers', 'attachments']);
});

it('passes custom headers and skips reserved ones', function (): void {
    $email = new Email()
        ->from('sender@example.com')
        ->to('rcpt@example.com')
        ->subject('s')
        ->text('t');

    $email->getHeaders()->addTextHeader('X-Tenant-Id', 'org_abc');
    $email->getHeaders()->addTextHeader('X-Campaign', 'spring');

    $payload = buildPayload($email);

    expect($payload['headers'])->toHaveKey('X-Tenant-Id', 'org_abc');
    expect($payload['headers'])->toHaveKey('X-Campaign', 'spring');
    expect($payload['headers'])->not->toHaveKey('From');
    expect($payload['headers'])->not->toHaveKey('Subject');
    expect($payload['headers'])->not->toHaveKey('Content-Type');
});

it('base64-encodes attachments with filename, type, and attachment disposition', function (): void {
    $email = new Email()
        ->from('sender@example.com')
        ->to('rcpt@example.com')
        ->subject('s')
        ->text('t')
        ->attach('binary-bytes', 'report.pdf', 'application/pdf');

    $payload = buildPayload($email);

    expect($payload['attachments'])->toHaveCount(1);
    expect($payload['attachments'][0])->toMatchArray([
        'content' => base64_encode('binary-bytes'),
        'filename' => 'report.pdf',
        'type' => 'application/pdf',
        'disposition' => 'attachment',
    ]);
});

it('rejects inline attachments since Cloudflare does not support it', function (): void {
    $inline = new DataPart('img-bytes', 'logo.png', 'image/png')->asInline();

    $email = new Email()
        ->from('sender@example.com')
        ->to('rcpt@example.com')
        ->subject('s')
        ->html('<img src="cid:logo">')
        ->addPart($inline);

    expect(fn () => buildPayload($email))
        ->toThrow(CloudflareTransportException::class, 'does not support inline attachments');
});

it('handles multiple to-recipients as an array of formatted strings', function (): void {
    $email = new Email()
        ->from('sender@example.com')
        ->to('a@example.com', new Address('b@example.com', 'Bee'))
        ->subject('s')
        ->text('t');

    $payload = buildPayload($email);

    expect($payload['to'])->toBe(['a@example.com', 'Bee <b@example.com>']);
});
