<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Transport;

use Junges\CloudflareMail\Cloudflare\Client;
use Junges\CloudflareMail\Cloudflare\PayloadBuilder;
use Junges\CloudflareMail\Exceptions\CloudflareTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;

/** @internal This class is not part of the package's public API and may change in any release. */
final class CloudflareTransport extends AbstractTransport
{
    public function __construct(
        private readonly Client $client,
        private readonly PayloadBuilder $payloadBuilder,
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'cloudflare';
    }

    protected function doSend(SentMessage $message): void
    {
        try {
            $email = $this->asEmail($message);

            $payload = $this->payloadBuilder->build($email, $message->getEnvelope());

            $this->client->send($payload);
        } catch (CloudflareTransportException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }

    private function asEmail(SentMessage $message): Email
    {
        $original = $message->getOriginalMessage();

        if ($original instanceof Email) {
            return $original;
        }

        if ($original instanceof Message) {
            return MessageConverter::toEmail($original);
        }

        throw new TransportException(sprintf(
            'The Cloudflare transport requires a [%s] or [%s] message.[%s] given.',
            Email::class,
            Message::class,
            $original::class,
        ));
    }
}
