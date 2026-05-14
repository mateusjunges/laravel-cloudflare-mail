<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Transport;

use Junges\CloudflareMail\Cloudflare\Client;
use Junges\CloudflareMail\Cloudflare\PayloadBuilder;
use Junges\CloudflareMail\Exceptions\CloudflareTransportException;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;

final class CloudflareTransport extends AbstractTransport
{
    public function __construct(
        private readonly Client $client,
        private readonly PayloadBuilder $payloadBuilder = new PayloadBuilder(),
    ) {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'cloudflare+api://api.cloudflare.com';
    }

    protected function doSend(SentMessage $message): void
    {
        $original = $message->getOriginalMessage();

        if (! $original instanceof Email) {
            throw new TransportException(sprintf(
                'The Cloudflare transport requires a Symfony Email instance, [%s] given.',
                $original::class,
            ));
        }

        $payload = $this->payloadBuilder->build($original, $message->getEnvelope());

        try {
            $this->client->send($payload);
        } catch (CloudflareTransportException $e) {
            throw new TransportException($e->getMessage(), 0, $e);
        }
    }
}
