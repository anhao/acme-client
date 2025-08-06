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

namespace ALAPI\Acme;

use ALAPI\Acme\Accounts\Contracts\AcmeAccountInterface;
use ALAPI\Acme\Accounts\Contracts\HttpClientInterface;
use ALAPI\Acme\Endpoints\Account;
use ALAPI\Acme\Endpoints\Certificate;
use ALAPI\Acme\Endpoints\Directory;
use ALAPI\Acme\Endpoints\DomainValidation;
use ALAPI\Acme\Endpoints\Nonce;
use ALAPI\Acme\Endpoints\Order;
use ALAPI\Acme\Endpoints\RenewalInfo;
use ALAPI\Acme\Exceptions\AcmeAccountException;
use ALAPI\Acme\Http\Clients\ClientFactory;
use ALAPI\Acme\Management\RenewalManager;
use Psr\Log\LoggerInterface;

class AcmeClient
{
    private const PRODUCTION_URL = 'https://acme-v02.api.letsencrypt.org/directory';

    private const STAGING_URL = 'https://acme-staging-v02.api.letsencrypt.org/directory';

    private ?Directory $directory = null;

    private ?Nonce $nonce = null;

    private ?Account $account = null;

    private ?Order $order = null;

    private ?DomainValidation $domainValidation = null;

    private ?Certificate $certificate = null;

    private ?RenewalInfo $renewalInfo = null;

    public function __construct(
        bool $staging = false,
        private ?AcmeAccountInterface $localAccount = null,
        private ?LoggerInterface $logger = null,
        private ?HttpClientInterface $httpClient = null,
        private ?string $baseUrl = null,
    ) {
        if (empty($this->baseUrl)) {
            $this->baseUrl = $staging ? self::STAGING_URL : self::PRODUCTION_URL;
        }
    }

    public function setLocalAccount(AcmeAccountInterface $account): self
    {
        $this->localAccount = $account;

        return $this;
    }

    public function localAccount(): AcmeAccountInterface
    {
        if ($this->localAccount === null) {
            throw new AcmeAccountException('No account set.');
        }

        return $this->localAccount;
    }

    public function directory(): Directory
    {
        if ($this->directory === null) {
            $this->directory = new Directory($this);
        }
        return $this->directory;
    }

    public function nonce(): Nonce
    {
        if ($this->nonce === null) {
            $this->nonce = new Nonce($this);
        }
        return $this->nonce;
    }

    public function account(): Account
    {
        if ($this->account === null) {
            $this->account = new Account($this);
        }
        return $this->account;
    }

    public function order(): Order
    {
        if ($this->order === null) {
            $this->order = new Order($this);
        }
        return $this->order;
    }

    public function domainValidation(): DomainValidation
    {
        if ($this->domainValidation === null) {
            $this->domainValidation = new DomainValidation($this);
        }
        return $this->domainValidation;
    }

    public function certificate(): Certificate
    {
        if ($this->certificate === null) {
            $this->certificate = new Certificate($this);
        }
        return $this->certificate;
    }

    public function renewalInfo(): RenewalInfo
    {
        if ($this->renewalInfo === null) {
            $this->renewalInfo = new RenewalInfo($this);
        }
        return $this->renewalInfo;
    }

    public function renewalManager(int $defaultRenewalDays = 30): RenewalManager
    {
        return new RenewalManager($this, $defaultRenewalDays);
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getHttpClient(): HttpClientInterface
    {
        // Create a default client if none is set.
        if ($this->httpClient === null) {
            $this->httpClient = ClientFactory::create();
        }

        return $this->httpClient;
    }

    public function setHttpClient(HttpClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function logger(string $level, string $message, array $context = []): void
    {
        if ($this->logger instanceof LoggerInterface) {
            $this->logger->log($level, $message, $context);
        }
    }
}
