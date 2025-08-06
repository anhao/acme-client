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

namespace ALAPI\Acme\Data\Transfer;

use ALAPI\Acme\Data\AbstractData;
use ALAPI\Acme\Http\ResponseHelper;
use ALAPI\Acme\Utils\Arr;
use ALAPI\Acme\Utils\Url;
use Psr\Http\Message\ResponseInterface;

class OrderData extends AbstractData
{
    public function __construct(
        public string $id,
        public string $url,
        public string $status,
        public string $expires,
        public array $identifiers,
        public array $domainValidationUrls,
        public string $finalizeUrl,
        public string $accountUrl,
        public ?string $certificateUrl,
        public bool $finalized = false,
        public ?string $replaces = null,
    ) {
    }

    public static function fromResponse(ResponseInterface $response, string $accountUrl = '', string $requestedUrl = ''): OrderData
    {
        $url = ResponseHelper::getHeaderValue($response, 'location');

        if (empty($url)) {
            $url = $requestedUrl; // Need to pass in request URL from outside
        }

        $url = trim(rtrim($url, '?'));
        $body = ResponseHelper::getJsonBody($response);

        return new self(
            id: Url::extractId($url),
            url: $url,
            status: $body['status'],
            expires: $body['expires'],
            identifiers: $body['identifiers'],
            domainValidationUrls: $body['authorizations'],
            finalizeUrl: $body['finalize'],
            accountUrl: $accountUrl,
            certificateUrl: Arr::get($body, 'certificate'),
            replaces: Arr::get($body, 'replaces'),
        );
    }

    public function setCertificateUrl(string $url): void
    {
        $this->certificateUrl = $url;
        $this->finalized = true;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    public function isFinalized(): bool
    {
        return $this->finalized || $this->isValid();
    }

    public function isNotFinalized(): bool
    {
        return ! $this->isFinalized();
    }

    /**
     * Check if this order is ARI suggested renewal.
     */
    public function isARIRenewal(): bool
    {
        return ! empty($this->replaces);
    }

    /**
     * Get replaced certificate ID.
     */
    public function getReplacedCertificateId(): ?string
    {
        return $this->replaces;
    }
}
