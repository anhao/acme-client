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

namespace ALAPI\Acme\Http\Clients;

use ALAPI\Acme\Accounts\Contracts\HttpClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Factory for creating HTTP clients.
 */
class ClientFactory
{
    /**
     * Create the default HTTP client.
     * Uses Guzzle with PSR-18 compliance.
     */
    public static function create(int $timeout = 10, array $options = [], ?LoggerInterface $logger = null): HttpClientInterface
    {
        return new GuzzleClient($timeout, $options, $logger);
    }
}
