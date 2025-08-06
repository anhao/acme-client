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

use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Psr\Log\LoggerInterface;

/**
 * Logging middleware for HTTP requests and responses.
 */
class LoggingMiddleware
{
    /**
     * Add logging middleware to Guzzle handler stack.
     */
    public static function create(LoggerInterface $logger, ?string $template = null): callable
    {
        $template = $template ?? MessageFormatter::CLF;

        return Middleware::log(
            $logger,
            new MessageFormatter($template)
        );
    }

    /**
     * Create detailed logging middleware with request/response bodies.
     */
    public static function createDetailed(LoggerInterface $logger): callable
    {
        $template = 'HTTP {method} {uri} - Status: {code} - Request: {req_body} - Response: {res_body}';

        return Middleware::log(
            $logger,
            new MessageFormatter($template)
        );
    }

    /**
     * Create debug logging middleware for development.
     */
    public static function createDebug(LoggerInterface $logger): callable
    {
        $template = "{method} {uri} HTTP/{version} {req_body}\nHTTP/{version} {code} {phrase}\n{res_body}";

        return Middleware::log(
            $logger,
            new MessageFormatter($template)
        );
    }
}
