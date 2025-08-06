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
use ALAPI\Acme\AcmeClient;
use ALAPI\Acme\Endpoints\Directory;
use ALAPI\Acme\Management\RenewalManager;
use ALAPI\Acme\Utils\AccountStorage;

describe('Account Management Integration', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/acme-integration-test-' . uniqid();
        mkdir($this->tempDir, 0700, true);
    });

    afterEach(function () {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    });

    it('complete account lifecycle', function () {
        // 1. Create new account
        $account = Account::createECC('P-384');

        expect($account->exists())->toBeTrue()
            ->and($account->getKeyType())->toBe('ECC')
            ->and($account->getKeySize())->toBe('secp384r1');

        // 2. Save to files
        AccountStorage::saveToFiles($account, $this->tempDir, 'lifecycle');

        expect(AccountStorage::exists($this->tempDir, 'lifecycle'))->toBeTrue();

        // 3. Load from files
        $loadedAccount = AccountStorage::loadFromFiles($this->tempDir, 'lifecycle');

        expect($loadedAccount->getPrivateKey())->toBe($account->getPrivateKey())
            ->and($loadedAccount->getPublicKey())->toBe($account->getPublicKey());

        // 4. Integrate with ACME client
        $client = new AcmeClient(staging: true);
        $client->setLocalAccount($loadedAccount);

        expect($client->localAccount())->toBe($loadedAccount);

        // 5. Get various endpoints
        $directory = $client->directory();
        $accountEndpoint = $client->account();
        $renewalManager = $client->renewalManager();

        expect($directory)->toBeInstanceOf(Directory::class)
            ->and($accountEndpoint)->toBeInstanceOf(ALAPI\Acme\Endpoints\Account::class)
            ->and($renewalManager)->toBeInstanceOf(RenewalManager::class);
    });

    it('account storage convenience methods integration', function () {
        // Use convenience method to create and save
        $account1 = AccountStorage::createAndSave($this->tempDir, 'convenience', 'RSA', 2048);

        expect($account1->getKeyType())->toBe('RSA')
            ->and($account1->getKeySize())->toBe(2048)
            ->and(AccountStorage::exists($this->tempDir, 'convenience'))->toBeTrue();

        // Using loadOrCreate should load existing account
        $account2 = AccountStorage::loadOrCreate($this->tempDir, 'convenience', 'ECC', 'P-256');

        expect($account2->getPrivateKey())->toBe($account1->getPrivateKey())
            ->and($account2->getKeyType())->toBe('RSA'); // Should be existing RSA, not P-256

        // New name should create new account
        $account3 = AccountStorage::loadOrCreate($this->tempDir, 'new-account', 'ECC', 'P-521');

        expect($account3->getKeyType())->toBe('ECC')
            ->and($account3->getKeySize())->toBe('secp521r1')
            ->and($account3->getPrivateKey())->not->toBe($account1->getPrivateKey());
    });

    it('multiple accounts can be managed simultaneously', function () {
        // Create multiple accounts of different types
        $rsaAccount = AccountStorage::createAndSave($this->tempDir, 'rsa-account', 'RSA', 2048);
        $eccP256Account = AccountStorage::createAndSave($this->tempDir, 'ecc-p256', 'ECC', 'P-256');
        $eccP384Account = AccountStorage::createAndSave($this->tempDir, 'ecc-p384', 'ECC', 'P-384');
        $eccP521Account = AccountStorage::createAndSave($this->tempDir, 'ecc-p521', 'ECC', 'P-521');

        // Verify all accounts are correctly saved
        expect(AccountStorage::exists($this->tempDir, 'rsa-account'))->toBeTrue()
            ->and(AccountStorage::exists($this->tempDir, 'ecc-p256'))->toBeTrue()
            ->and(AccountStorage::exists($this->tempDir, 'ecc-p384'))->toBeTrue()
            ->and(AccountStorage::exists($this->tempDir, 'ecc-p521'))->toBeTrue();

        // Verify properties of each account
        $loadedRsa = AccountStorage::loadFromFiles($this->tempDir, 'rsa-account');
        $loadedP256 = AccountStorage::loadFromFiles($this->tempDir, 'ecc-p256');
        $loadedP384 = AccountStorage::loadFromFiles($this->tempDir, 'ecc-p384');
        $loadedP521 = AccountStorage::loadFromFiles($this->tempDir, 'ecc-p521');

        expect($loadedRsa->getKeyType())->toBe('RSA')
            ->and($loadedRsa->getKeySize())->toBe(2048)
            ->and($loadedP256->getKeyType())->toBe('ECC')
            ->and($loadedP256->getKeySize())->toBe('prime256v1')
            ->and($loadedP384->getKeyType())->toBe('ECC')
            ->and($loadedP384->getKeySize())->toBe('secp384r1')
            ->and($loadedP521->getKeyType())->toBe('ECC')
            ->and($loadedP521->getKeySize())->toBe('secp521r1');

        // All private keys should be different
        $privateKeys = [
            $loadedRsa->getPrivateKey(),
            $loadedP256->getPrivateKey(),
            $loadedP384->getPrivateKey(),
            $loadedP521->getPrivateKey(),
        ];

        expect(array_unique($privateKeys))->toHaveCount(4);
    });

    it('accounts can be shared between different clients', function () {
        // Create account
        $account = AccountStorage::createAndSave($this->tempDir, 'shared', 'ECC', 'P-256');

        // Use the same account in multiple clients
        $prodClient = new AcmeClient(staging: false);
        $stagingClient = new AcmeClient(staging: true);

        $prodClient->setLocalAccount($account);
        $stagingClient->setLocalAccount($account);

        expect($prodClient->localAccount())->toBe($account)
            ->and($stagingClient->localAccount())->toBe($account)
            ->and($prodClient->getBaseUrl())->toContain('acme-v02.api.letsencrypt.org')
            ->and($stagingClient->getBaseUrl())->toContain('acme-staging-v02.api.letsencrypt.org');
    });
});
