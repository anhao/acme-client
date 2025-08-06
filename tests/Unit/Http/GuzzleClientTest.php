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
use ALAPI\Acme\Accounts\Contracts\HttpClientInterface;
use ALAPI\Acme\Http\Clients\GuzzleClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

describe('GuzzleClient', function () {
    it('implements HttpClientInterface', function () {
        $client = new GuzzleClient();
        expect($client)->toBeInstanceOf(HttpClientInterface::class);
    });

    it('can create client instance', function () {
        $client = new GuzzleClient();
        expect($client)->toBeInstanceOf(GuzzleClient::class);
    });

    it('can set timeout', function () {
        $client = new GuzzleClient(60);
        expect($client)->toBeInstanceOf(GuzzleClient::class);
    });

    it('can create request object', function () {
        $request = new Request('GET', 'https://example.com');
        expect($request)->toBeInstanceOf(RequestInterface::class);
    });

    it('can create response object', function () {
        $response = new Response(200, [], '{"success": true}');
        expect($response)->toBeInstanceOf(ResponseInterface::class)
            ->and($response->getStatusCode())->toBe(200);
    });

    it('can set logger', function () {
        $logger = new class implements LoggerInterface {
            public function emergency(string|Stringable $message, array $context = []): void
            {
            }

            public function alert(string|Stringable $message, array $context = []): void
            {
            }

            public function critical(string|Stringable $message, array $context = []): void
            {
            }

            public function error(string|Stringable $message, array $context = []): void
            {
            }

            public function warning(string|Stringable $message, array $context = []): void
            {
            }

            public function notice(string|Stringable $message, array $context = []): void
            {
            }

            public function info(string|Stringable $message, array $context = []): void
            {
            }

            public function debug(string|Stringable $message, array $context = []): void
            {
            }

            public function log($level, string|Stringable $message, array $context = []): void
            {
            }
        };

        $client = new GuzzleClient(30, [], $logger);
        expect($client)->toBeInstanceOf(GuzzleClient::class);
    });
});
