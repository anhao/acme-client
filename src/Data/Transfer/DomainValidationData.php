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
use ALAPI\Acme\Enums\AuthorizationChallengeEnum;
use ALAPI\Acme\Http\ResponseHelper;
use ALAPI\Acme\Utils\Arr;
use ALAPI\Acme\Utils\Url;
use Psr\Http\Message\ResponseInterface;

class DomainValidationData extends AbstractData
{
    public function __construct(
        public string $id,
        public string $url,
        public string $status,
        public string $expires,
        public array $identifier,
        public array $file,
        public array $dns,
        public string $type = '',
    ) {
    }

    public static function fromResponse(ResponseInterface $response, string $requestedUrl = ''): DomainValidationData
    {
        $body = ResponseHelper::getJsonBody($response);

        return new self(
            id: Url::extractId($requestedUrl),
            url: $requestedUrl,
            status: $body['status'],
            expires: $body['expires'],
            identifier: $body['identifier'],
            file: Arr::find($body['challenges'], 'type', 'http-01'),
            dns: Arr::find($body['challenges'], 'type', 'dns-01'),
        );
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isValid(): bool
    {
        return $this->status === 'valid';
    }

    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    public function hasErrors(): bool
    {
        if (array_key_exists('error', $this->file) && ! empty($this->file['error'])) {
            return true;
        }

        if (array_key_exists('error', $this->dns) && ! empty($this->dns['error'])) {
            return true;
        }

        return false;
    }

    public function getErrors(): array
    {
        if ($this->hasErrors()) {
            $data = [];

            $data[] = [
                'domainValidationType' => AuthorizationChallengeEnum::HTTP->value,
                'error' => Arr::get($this->file, 'error'),
            ];

            $data[] = [
                'domainValidationType' => AuthorizationChallengeEnum::DNS->value,
                'error' => Arr::get($this->dns, 'error'),
            ];

            return $data;
        }

        return [];
    }
}
