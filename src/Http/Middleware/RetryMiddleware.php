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

namespace ALAPI\Acme\Http\Middleware;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Throwable;

/**
 * Retry middleware for handling transient failures.
 */
class RetryMiddleware
{
    /**
     * Create retry middleware with exponential backoff.
     */
    public static function create(int $maxRetries = 3, int $delay = 1000): callable
    {
        return Middleware::retry(
            self::createDecider($maxRetries),
            self::createDelayFunction($delay)
        );
    }

    /**
     * Create a retry decider function.
     */
    private static function createDecider(int $maxRetries): callable
    {
        return function (
            int $retries,
            Request $request,
            ?Response $response = null,
            ?Throwable $exception = null
        ) use ($maxRetries): bool {
            // Exceeded maximum retry count
            if ($retries >= $maxRetries) {
                return false;
            }

            // Network connection exception - retry
            if ($exception instanceof ConnectException) {
                return true;
            }

            // HTTP status code 0 (Let's Encrypt API issue) - retry
            if ($response && $response->getStatusCode() === 0) {
                return true;
            }

            // 5xx server error - retry
            if ($response && $response->getStatusCode() >= 500) {
                return true;
            }

            // 429 Too Many Requests - retry
            if ($response && $response->getStatusCode() === 429) {
                return true;
            }

            return false;
        };
    }

    /**
     * Create delay function with exponential backoff.
     */
    private static function createDelayFunction(int $baseDelay): callable
    {
        return function (int $numberOfRetries) use ($baseDelay): int {
            // Exponential backoff: delay * 2^retries
            return $baseDelay * (2 ** $numberOfRetries);
        };
    }
}
