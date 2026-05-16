<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Cloudflare;

use Junges\CloudflareMail\Contracts\CloudflareTypes;
use Junges\CloudflareMail\Exceptions\CloudflareTransportException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Part\DataPart;
use Throwable;

/**
 * @phpstan-import-type CloudflarePayload from CloudflareTypes
 * @phpstan-import-type CloudflareAttachment from CloudflareTypes
 */
final class PayloadBuilder
{
    /** @var list<string> */
    private const array RESERVED_HEADERS = [
        'from', 'to', 'cc', 'bcc', 'reply-to', 'sender', 'subject',
        'date', 'message-id', 'mime-version', 'content-type', 'content-transfer-encoding',
    ];

    /** @return CloudflarePayload */
    public function build(Email $email, Envelope $envelope): array
    {
        $payload = [
            'from' => $this->formatAddress($envelope->getSender()),
            'to' => $this->formatRecipients($email->getTo() ?: $envelope->getRecipients()),
            'subject' => $email->getSubject() ?? '',
        ];

        if ($cc = $this->formatRecipients($email->getCc())) {
            $payload['cc'] = $cc;
        }

        if ($bcc = $this->formatRecipients($email->getBcc())) {
            $payload['bcc'] = $bcc;
        }

        if ($replyTo = $email->getReplyTo()) {
            $payload['reply_to'] = $this->formatAddress($replyTo[0]);
        }

        if (($text = $this->normalizeBody($email->getTextBody())) !== null) {
            $payload['text'] = $text;
        }

        if (($html = $this->normalizeBody($email->getHtmlBody())) !== null) {
            $payload['html'] = $html;
        }

        if ($headers = $this->collectCustomHeaders($email)) {
            $payload['headers'] = $headers;
        }

        if ($attachments = $this->collectAttachments($email)) {
            $payload['attachments'] = $attachments;
        }

        return $payload;
    }

    private function formatAddress(Address $address): string
    {
        return $address->getName() !== ''
            ? sprintf('%s <%s>', $address->getName(), $address->getAddress())
            : $address->getAddress();
    }

    /**
     * @param  array<Address>  $addresses
     * @return list<string>
     */
    private function formatRecipients(array $addresses): array
    {
        return array_values(array_map($this->formatAddress(...), $addresses));
    }

    /** @param  string|resource|null  $body */
    private function normalizeBody(mixed $body): ?string
    {
        return match (true) {
            $body === null => null,
            is_resource($body) => stream_get_contents($body) ?: null,
            default => (string) $body,
        };
    }

    /** @return array<string, string> */
    private function collectCustomHeaders(Email $email): array
    {
        $headers = [];

        foreach ($email->getHeaders()->all() as $header) {
            if (! $header instanceof HeaderInterface) {
                continue;
            }

            if (in_array(mb_strtolower($header->getName()), self::RESERVED_HEADERS, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        return $headers;
    }

    /**
     * @return list<CloudflareAttachment>
     *
     * @throws CloudflareTransportException|Throwable
     */
    private function collectAttachments(Email $email): array
    {
        return array_values(array_map(
            function (DataPart $part): array {
                throw_if(
                    $part->getDisposition() === 'inline',
                    CloudflareTransportException::inlineAttachmentsNotSupported(),
                );

                return [
                    'content' => base64_encode($part->getBody()),
                    'filename' => $part->getFilename() ?? 'attachment',
                    'type' => sprintf('%s/%s', $part->getMediaType(), $part->getMediaSubtype()),
                    'disposition' => 'attachment',
                ];
            },
            $email->getAttachments(),
        ));
    }
}
