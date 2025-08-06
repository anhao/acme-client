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
use ALAPI\Acme\Utils\Url;
use Psr\Http\Message\ResponseInterface;

class AccountData extends AbstractData
{
    public function __construct(
        public string $id,
        public string $status,
        public ?string $orders = null,
        public ?array $contact = null,
        public ?bool $termsOfServiceAgreed = null,
        public ?array $key = null,
        public ?array $externalAccountBinding = null,
        // Non-standard fields for implementation convenience
        public ?string $url = null,
        public ?string $agreement = null,
        public ?string $createdAt = null,
    ) {
    }

    public static function fromResponse(ResponseInterface $response): AccountData
    {
        $url = trim(ResponseHelper::getHeaderValue($response, 'location', ''));
        $body = ResponseHelper::getJsonBody($response);
        var_dump($body);

        return new self(
            id: Url::extractId($url),
            status: $body['status'],
            orders: $body['orders'] ?? '',
            contact: $body['contact'] ?? null,
            termsOfServiceAgreed: $body['termsOfServiceAgreed'] ?? null,
            key: $body['key'] ?? null,
            externalAccountBinding: $body['externalAccountBinding'] ?? null,
            url: $url ?: null,
            agreement: $body['agreement'] ?? null,
            createdAt: $body['createdAt'] ?? null
        );
    }
}
