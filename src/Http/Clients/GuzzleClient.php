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
use ALAPI\Acme\Exceptions\AcmeException;
use ALAPI\Acme\Http\Middleware\LoggingMiddleware;
use GuzzleHttp\Client as GuzzleHttpClient;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Guzzle-based HTTP client that implements the ACME client interface.
 */
class GuzzleClient implements HttpClientInterface
{
    private GuzzleHttpClient $guzzle;

    private int $timeout = 10;

    public function __construct(int $timeout = 10, array $options = [], ?LoggerInterface $logger = null)
    {
        $this->timeout = $timeout;

        $stack = HandlerStack::create();

        if ($logger) {
            $stack->push(LoggingMiddleware::create($logger), 'logging');
        }

        $this->guzzle = new GuzzleHttpClient(array_merge([
            'handler' => $stack,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->timeout,
            'http_errors' => false,
            'verify' => true,
            'headers' => [
                'User-Agent' => 'ALAPI/Acme Client v1.0',
                'Accept' => 'application/json',
            ],
        ], $options));
    }

    /**
     * Send a HEAD request.
     */
    public function head(string $url): ResponseInterface
    {
        try {
            return $this->guzzle->head($url);
        } catch (GuzzleException $e) {
            throw new AcmeException('HEAD request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Send a GET request.
     */
    public function get(string $url, array $headers = [], array $arguments = [], int $maxRedirects = 0): ResponseInterface
    {
        try {
            $options = [
                'headers' => array_merge([
                    'Content-Type' => 'application/json',
                ], $headers),
                'query' => $arguments,
            ];

            if ($maxRedirects > 0) {
                $options['allow_redirects'] = ['max' => $maxRedirects];
            } else {
                $options['allow_redirects'] = false;
            }

            return $this->guzzle->get($url, $options);
        } catch (GuzzleException $e) {
            throw new AcmeException('GET request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Send a POST request.
     */
    public function post(string $url, array $payload = [], array $headers = [], int $maxRedirects = 0): ResponseInterface
    {
        try {
            $defaultHeaders = [
                'Content-Type' => 'application/jose+json',
            ];

            $options = [
                'headers' => array_merge($defaultHeaders, $headers),
                'json' => $payload,
            ];

            if ($maxRedirects > 0) {
                $options['allow_redirects'] = ['max' => $maxRedirects];
            } else {
                $options['allow_redirects'] = false;
            }

            return $this->guzzle->post($url, $options);
        } catch (GuzzleException $e) {
            throw new AcmeException('POST request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * PSR-18 sendRequest implementation.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        try {
            return $this->guzzle->sendRequest($request);
        } catch (GuzzleException $e) {
            throw new AcmeException('PSR-18 request failed: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get the underlying Guzzle client for advanced usage.
     */
    public function getGuzzleClient(): GuzzleHttpClient
    {
        return $this->guzzle;
    }
}
