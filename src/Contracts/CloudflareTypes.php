<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Cloudflare;

/**
 * Holds the array-shape definitions for the Cloudflare Email Service wire format.
 *
 * This interface has no runtime behavior. It exists only as an import source for
 * `@phpstan-import-type`, so the shapes are declared once and PhpStorm and PHPStan
 * both resolve them.
 *
 * @phpstan-type CloudflareAttachment array{content: string, filename: string, type: string, disposition: 'attachment'|'inline'}
 * @phpstan-type CloudflarePayload array{from: string, to: list<string>, subject: string, cc?: list<string>, bcc?: list<string>, reply_to?: string, text?: string, html?: string, headers?: array<string, string>, attachments?: list<CloudflareAttachment>}
 * @phpstan-type CloudflareResponseBody array{success?: bool, errors?: list<array{code?: int, message?: string}>, result?: array{delivered?: list<string>, permanent_bounces?: list<string>, queued?: list<string>}|null}
 */
interface CloudflareTypes {}
