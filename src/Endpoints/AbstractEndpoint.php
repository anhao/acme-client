<?php

declare(strict_types=1);
/**
 * This file is part of ALAPI.
 *
 * @package  ALAPI\Acme
 * @link     https://www.alapi.cn
 * @license  MIT License
 * @copyright ALAPI <im@alone88.cn>
 */

namespace ALAPI\Acme\Endpoints;

use ALAPI\Acme\AcmeClient;
use ALAPI\Acme\Http\ResponseHelper;
use ALAPI\Acme\Security\Keys\KeyId;
use Psr\Http\Message\ResponseInterface;

abstract class AbstractEndpoint
{
    public function __construct(protected AcmeClient $client)
    {
    }

    protected function createKeyId(string $accountUrl, string $url, ?array $payload = null): array
    {
        return KeyId::generate(
            $this->client->localAccount()->getPrivateKey(),
            $accountUrl,
            $url,
            $this->client->nonce()->getNew(),
            $payload
        );
    }

    protected function getAccountPrivateKey(): string
    {
        return $this->client->localAccount()->getPrivateKey();
    }

    protected function logResponse(string $level, string $message, ResponseInterface $response, array $additionalContext = []): void
    {
        $this->client->logger($level, $message, array_merge([
            'url' => 'N/A', // PSR-7 doesn't include requested URL
            'status' => ResponseHelper::getStatusCode($response),
            'headers' => $response->getHeaders(),
            'body' => ResponseHelper::parseBody($response),
        ], $additionalContext));
    }
}
