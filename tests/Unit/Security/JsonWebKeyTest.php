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
use ALAPI\Acme\Security\Keys\JsonWebKey;

describe('JsonWebKey', function () {
    it('can generate JWK from RSA account', function () {
        $account = Account::createRSA(2048);
        $jwk = JsonWebKey::compute($account->getPrivateKey());

        expect($jwk)->toBeArray()
            ->and($jwk)->toHaveKey('kty')
            ->and($jwk['kty'])->toBe('RSA')
            ->and($jwk)->toHaveKey('n')
            ->and($jwk)->toHaveKey('e');
    });

    it('can generate JWK from ECC account', function () {
        $account = Account::createECC('P-256');
        $jwk = JsonWebKey::compute($account->getPrivateKey());

        expect($jwk)->toBeArray()
            ->and($jwk)->toHaveKey('kty')
            ->and($jwk['kty'])->toBe('EC')
            ->and($jwk)->toHaveKey('crv')
            ->and($jwk)->toHaveKey('x')
            ->and($jwk)->toHaveKey('y');
    });

    it('JWK contains correct curve information', function () {
        $accountP256 = Account::createECC('P-256');
        $jwkP256 = JsonWebKey::compute($accountP256->getPrivateKey());

        expect($jwkP256['crv'])->toBe('P-256');

        $accountP384 = Account::createECC('P-384');
        $jwkP384 = JsonWebKey::compute($accountP384->getPrivateKey());

        expect($jwkP384['crv'])->toBe('P-384');
    });

    it('same key should generate consistent JWK', function () {
        $account = Account::createRSA(2048);
        $privateKey = $account->getPrivateKey();

        $jwk1 = JsonWebKey::compute($privateKey);
        $jwk2 = JsonWebKey::compute($privateKey);

        expect($jwk1)->toBe($jwk2);
    });
});
