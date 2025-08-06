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
use ALAPI\Acme\Exceptions\AcmeException;

describe('Account', function () {
    it('can create RSA account', function () {
        $account = Account::createRSA(2048);

        expect($account)->toBeInstanceOf(Account::class)
            ->and($account->exists())->toBeTrue()
            ->and($account->getPrivateKey())->toContain('-----BEGIN PRIVATE KEY-----')
            ->and($account->getPublicKey())->toContain('-----BEGIN PUBLIC KEY-----');
    });

    it('can create ECC account', function () {
        $account = Account::createECC('P-384');

        expect($account)->toBeInstanceOf(Account::class)
            ->and($account->exists())->toBeTrue()
            ->and($account->getPrivateKey())->toContain('-----BEGIN PRIVATE KEY-----')
            ->and($account->getPublicKey())->toContain('-----BEGIN PUBLIC KEY-----');
    });

    it('can create account from private key string', function () {
        $originalAccount = Account::createRSA(2048);
        $privateKey = $originalAccount->getPrivateKey();

        $newAccount = Account::fromPrivateKey($privateKey);

        expect($newAccount->getPrivateKey())->toBe($privateKey)
            ->and($newAccount->exists())->toBeTrue();
    });

    it('can manually specify public and private keys', function () {
        $originalAccount = Account::createRSA(2048);
        $privateKey = $originalAccount->getPrivateKey();
        $publicKey = $originalAccount->getPublicKey();

        $account = new Account($privateKey, $publicKey);

        expect($account->getPrivateKey())->toBe($privateKey)
            ->and($account->getPublicKey())->toBe($publicKey)
            ->and($account->exists())->toBeTrue();
    });

    it('invalid private key should throw exception', function () {
        expect(fn () => Account::fromPrivateKey('invalid-key'))
            ->toThrow(AcmeException::class, 'Invalid private key provided.');
    });
});
