<?php declare(strict_types=1);

namespace Junges\CloudflareMail\Cloudflare;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;
use Junges\CloudflareMail\Exceptions\CloudflareTransportException;
use SensitiveParameter;

/**
 * @phpstan-import-type CloudflarePayload from CloudflareTypes
 * @phpstan-import-type CloudflareResponseBody from CloudflareTypes
 */
final readonly class Client
{
    private const string BASE_URL = 'https://api.cloudflare.com/client/v4';

    public function __construct(
        private string $accountId,
        #[SensitiveParameter] private string $apiToken,
        private Factory $http,
    ) {}

    /** @param  CloudflarePayload  $payload */
    public function send(array $payload): void
    {
        $response = $this->post($payload);

        /** @var CloudflareResponseBody $body */
        $body = $response->json() ?? [];

        $this->ensureAccepted($response, $body);
        $this->ensureNoBounces($response, $body);
    }

    /** @param  CloudflarePayload  $payload */
    private function post(array $payload): Response
    {
        try {
            $url = sprintf('%s/accounts/%s/email/sending/send', self::BASE_URL, $this->accountId);

            return $this->http
                ->withToken($this->apiToken)
                ->acceptJson()
                ->asJson()
                ->post($url, $payload);

        } catch (ConnectionException $e) {
            throw CloudflareTransportException::fromConnectionFailure($e);
        }
    }

    /** @param  CloudflareResponseBody  $body */
    private function ensureAccepted(Response $response, array $body): void
    {
        if ($response->successful() && data_get($body, 'success') === true) {
            return;
        }

        throw CloudflareTransportException::fromResponse($response, $body);
    }

    /**
     * @param  CloudflareResponseBody  $body
     *
     * @throws CloudflareTransportException
     */
    private function ensureNoBounces(Response $response, array $body): void
    {
        $bounces = data_get($body, 'result.permanent_bounces', []);

        throw_if(filled($bounces), CloudflareTransportException::fromBounces($response, $bounces));
    }
}
