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

use ALAPI\Acme\Exceptions\AcmeDirectoryException;
use ALAPI\Acme\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;

class Directory extends AbstractEndpoint
{
    private mixed $directoryData = null;

    public function all(): ResponseInterface
    {
        if ($this->directoryData !== null) {
            return $this->directoryData;
        }
        $response = $this->client
            ->getHttpClient()
            ->get($this->client->getBaseUrl());

        if (! ResponseHelper::isSuccess($response)) {
            $this->logResponse('error', 'Cannot get directory', $response);

            throw AcmeDirectoryException::fromResponse($response, 'Cannot get directory');
        }

        $this->directoryData = $response;

        return $response;
    }

    public function newNonce(): string
    {
        $body = ResponseHelper::getJsonBody($this->all());
        return $body['newNonce'];
    }

    public function newAccount(): string
    {
        $body = ResponseHelper::getJsonBody($this->all());
        return $body['newAccount'];
    }

    public function newOrder(): string
    {
        $body = ResponseHelper::getJsonBody($this->all());
        return $body['newOrder'];
    }

    public function getOrder(): string
    {
        $url = str_replace('new-order', 'order', $this->newOrder());

        return rtrim($url, '/') . '/';
    }

    public function revoke(): string
    {
        $body = ResponseHelper::getJsonBody($this->all());
        return $body['revokeCert'];
    }

    /**
     * Get ARI renewal information endpoint URL.
     * Returns null if CA does not support ARI.
     */
    public function renewalInfo(): ?string
    {
        $directory = ResponseHelper::getJsonBody($this->all());
        return $directory['renewalInfo'] ?? null;
    }

    /**
     * Check if ACME server supports ARI.
     */
    public function supportsARI(): bool
    {
        return $this->renewalInfo() !== null;
    }

    /**
     * Clear cached directory data (force refresh on next request).
     */
    public function clearCache(): void
    {
        $this->directoryData = null;
    }

    /**
     * Force refresh directory information.
     */
    public function refresh(): ResponseInterface
    {
        $this->clearCache();
        return $this->all();
    }
}
