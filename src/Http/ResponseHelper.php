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

namespace ALAPI\Acme\Http;

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseInterface;

/**
 * Helper class for working with PSR-7 responses in ACME context.
 */
class ResponseHelper
{
    /**
     * Parse response body as JSON array or return as string.
     */
    public static function parseBody(ResponseInterface $response): array|string
    {
        $body = (string) $response->getBody();

        if (empty($body)) {
            return $body;
        }

        // Try to parse JSON
        if (self::isJson($body)) {
            try {
                return json_decode($body, true, 512, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
            } catch (JsonException $e) {
                return $body;
            }
        }

        return $body;
    }

    /**
     * Get parsed JSON body or throw exception if not JSON.
     */
    public static function getJsonBody(ResponseInterface $response): array
    {
        $body = self::parseBody($response);

        if (! is_array($body)) {
            throw new InvalidArgumentException('Response body is not valid JSON');
        }

        return $body;
    }

    /**
     * Get status code (convenience method).
     */
    public static function getStatusCode(ResponseInterface $response): int
    {
        return $response->getStatusCode();
    }

    /**
     * Check if response is successful (2xx).
     */
    public static function isSuccess(ResponseInterface $response): bool
    {
        $code = $response->getStatusCode();
        return $code >= 200 && $code < 300;
    }

    /**
     * Check if response is client error (4xx).
     */
    public static function isClientError(ResponseInterface $response): bool
    {
        $code = $response->getStatusCode();
        return $code >= 400 && $code < 500;
    }

    /**
     * Check if response is server error (5xx).
     */
    public static function isServerError(ResponseInterface $response): bool
    {
        $code = $response->getStatusCode();
        return $code >= 500 && $code < 600;
    }

    /**
     * Get header value (first value if multiple).
     */
    public static function getHeaderValue(ResponseInterface $response, string $name, ?string $default = null): ?string
    {
        $headers = $response->getHeaderLine($name);
        return $headers ?: $default;
    }

    /**
     * Check if response has specific header.
     */
    public static function hasHeader(ResponseInterface $response, string $name): bool
    {
        return $response->hasHeader($name);
    }

    /**
     * Get error detail from ACME response.
     */
    public static function getErrorDetail(ResponseInterface $response): string
    {
        if (self::isSuccess($response)) {
            return '';
        }

        $body = self::parseBody($response);

        if (is_array($body)) {
            return $body['detail'] ?? $body['error'] ?? 'Unknown error';
        }

        return 'HTTP ' . $response->getStatusCode();
    }

    /**
     * Check if string is valid JSON.
     */
    private static function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
