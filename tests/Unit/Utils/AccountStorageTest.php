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
use ALAPI\Acme\Exceptions\AcmeAccountException;
use ALAPI\Acme\Utils\AccountStorage;

describe('AccountStorage', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/acme-test-' . uniqid();
        mkdir($this->tempDir, 0700, true);
    });

    afterEach(function () {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    });

    it('can save account to files', function () {
        $account = Account::createECC('P-256');

        AccountStorage::saveToFiles($account, $this->tempDir, 'test');

        $privateKeyFile = $this->tempDir . '/test-private.pem';
        $publicKeyFile = $this->tempDir . '/test-public.pem';

        expect(file_exists($privateKeyFile))->toBeTrue()
            ->and(file_exists($publicKeyFile))->toBeTrue();
    });

    it('can load account from files', function () {
        $originalAccount = Account::createRSA(2048);
        AccountStorage::saveToFiles($originalAccount, $this->tempDir, 'test');

        $loadedAccount = AccountStorage::loadFromFiles($this->tempDir, 'test');

        expect($loadedAccount)->toBeInstanceOf(Account::class)
            ->and($loadedAccount->getPrivateKey())->toBe($originalAccount->getPrivateKey())
            ->and($loadedAccount->getPublicKey())->toBe($originalAccount->getPublicKey());
    });

    it('can check if account files exist', function () {
        expect(AccountStorage::exists($this->tempDir, 'test'))->toBeFalse();

        $account = Account::createECC('P-384');
        AccountStorage::saveToFiles($account, $this->tempDir, 'test');

        expect(AccountStorage::exists($this->tempDir, 'test'))->toBeTrue();
    });

    it('can create new account and save', function () {
        $account = AccountStorage::createAndSave($this->tempDir, 'new-account', 'RSA', 2048);

        expect($account)->toBeInstanceOf(Account::class)
            ->and(AccountStorage::exists($this->tempDir, 'new-account'))->toBeTrue();
    });

    it('should throw exception for non-existent files', function () {
        expect(fn () => AccountStorage::loadFromFiles('/nonexistent', 'test'))
            ->toThrow(AcmeAccountException::class);
    });
});
