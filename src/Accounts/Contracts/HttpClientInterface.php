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

namespace ALAPI\Acme\Accounts\Contracts;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP client interface for ACME client operations.
 * Extends PSR-18 ClientInterface with ACME-specific convenience methods.
 * All methods return PSR-7 ResponseInterface objects.
 */
interface HttpClientInterface extends ClientInterface
{
    public function __construct(int $timeout = 10);

    /**
     * Send a HEAD request.
     */
    public function head(string $url): ResponseInterface;

    /**
     * Send a GET request.
     */
    public function get(string $url, array $headers = [], array $arguments = [], int $maxRedirects = 0): ResponseInterface;

    /**
     * Send a POST request.
     */
    public function post(string $url, array $payload = [], array $headers = [], int $maxRedirects = 0): ResponseInterface;
}
