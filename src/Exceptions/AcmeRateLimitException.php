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

namespace ALAPI\Acme\Exceptions;

use Psr\Http\Message\ResponseInterface;

/**
 * ACME rate limit exception.
 *
 * Thrown when requests exceed server rate limits
 */
class AcmeRateLimitException extends AcmeException
{
    private ?int $retryAfter = null;

    public static function fromResponse(ResponseInterface $response, string $defaultMessage = 'Rate limit exceeded'): static
    {
        $exception = parent::fromResponse($response, $defaultMessage);

        // Extract Retry-After header if present
        $retryAfterHeader = $response->getHeader('Retry-After');
        if (! empty($retryAfterHeader)) {
            $exception->retryAfter = (int) $retryAfterHeader[0];
        }

        return $exception;
    }

    /**
     * Get the number of seconds to wait before retrying.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
