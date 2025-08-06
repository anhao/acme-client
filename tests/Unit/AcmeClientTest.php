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
use ALAPI\Acme\Accounts\Account;
use ALAPI\Acme\Accounts\Contracts\HttpClientInterface;
use ALAPI\Acme\AcmeClient;
use ALAPI\Acme\Endpoints\Account as AccountEndpoint;
use ALAPI\Acme\Endpoints\Directory;
use ALAPI\Acme\Exceptions\AcmeAccountException;
use ALAPI\Acme\Management\RenewalManager;
use Psr\Log\LoggerInterface;

describe('AcmeClient', function () {
    beforeEach(function () {
        $this->account = Account::createECC('P-256');
    });

    it('can create client instance', function () {
        $client = new AcmeClient();

        expect($client)->toBeInstanceOf(AcmeClient::class)
            ->and($client->getBaseUrl())->toBe('https://acme-v02.api.letsencrypt.org/directory');
    });

    it('can create staging environment client', function () {
        $client = new AcmeClient(staging: true);

        expect($client->getBaseUrl())->toBe('https://acme-staging-v02.api.letsencrypt.org/directory');
    });

    it('can set custom base URL', function () {
        $customUrl = 'https://custom-acme.example.com/directory';
        $client = new AcmeClient(baseUrl: $customUrl);

        expect($client->getBaseUrl())->toBe($customUrl);
    });

    it('can set and get local account', function () {
        $client = new AcmeClient();
        $client->setLocalAccount($this->account);

        expect($client->localAccount())->toBe($this->account);
    });

    it('should throw exception when no account is set', function () {
        $client = new AcmeClient();

        expect(fn () => $client->localAccount())
            ->toThrow(AcmeAccountException::class, 'No account set.');
    });

    it('can get Directory endpoint', function () {
        $client = new AcmeClient();
        $directory = $client->directory();

        expect($directory)->toBeInstanceOf(Directory::class);

        // Multiple calls should return the same instance
        expect($client->directory())->toBe($directory);
    });

    it('can get Account endpoint', function () {
        $client = new AcmeClient();
        $account = $client->account();

        expect($account)->toBeInstanceOf(AccountEndpoint::class);

        // Multiple calls should return the same instance
        expect($client->account())->toBe($account);
    });

    it('can get renewal manager', function () {
        $client = new AcmeClient();
        $renewalManager = $client->renewalManager();

        expect($renewalManager)->toBeInstanceOf(RenewalManager::class);

        // Each call should return a new instance
        expect($client->renewalManager())->not->toBe($renewalManager);
    });

    it('can customize renewal manager default days', function () {
        $client = new AcmeClient();
        $renewalManager = $client->renewalManager(45);

        expect($renewalManager)->toBeInstanceOf(RenewalManager::class);
    });

    it('can get HTTP client', function () {
        $client = new AcmeClient();
        $httpClient = $client->getHttpClient();

        expect($httpClient)->toBeInstanceOf(HttpClientInterface::class);

        // Multiple calls should return the same instance
        expect($client->getHttpClient())->toBe($httpClient);
    });

    it('logger works correctly', function () {
        $logger = new class implements LoggerInterface {
            public array $logs = [];

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
                $this->logs[] = ['level' => 'info', 'message' => $message, 'context' => $context];
            }

            public function debug(string|Stringable $message, array $context = []): void
            {
            }

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->logs[] = ['level' => $level, 'message' => $message, 'context' => $context];
            }
        };

        $client = new AcmeClient(logger: $logger);
        $client->setLogger($logger);
        $client->logger('info', 'Test message', ['key' => 'value']);

        expect($logger->logs)->toHaveCount(1)
            ->and($logger->logs[0]['level'])->toBe('info')
            ->and($logger->logs[0]['message'])->toBe('Test message')
            ->and($logger->logs[0]['context'])->toBe(['key' => 'value']);
    });
});
