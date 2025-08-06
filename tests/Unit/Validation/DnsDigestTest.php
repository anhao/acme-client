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
use ALAPI\Acme\Validation\Challenges\DnsDigest;

describe('DnsDigest', function () {
    it('can generate DNS TXT record value', function () {
        $token = 'test-token-123';
        $keyAuthorization = 'key-auth-456';

        $digest = DnsDigest::make($token, $keyAuthorization);

        expect($digest)->toBeString()
            ->and(strlen($digest))->toBeGreaterThan(0);
    });

    it('same input should produce same digest', function () {
        $token = 'consistent-token';
        $keyAuthorization = 'consistent-key-auth';

        $digest1 = DnsDigest::make($token, $keyAuthorization);
        $digest2 = DnsDigest::make($token, $keyAuthorization);

        expect($digest1)->toBe($digest2);
    });

    it('different input should produce different digest', function () {
        $token1 = 'token-1';
        $token2 = 'token-2';
        $keyAuthorization = 'same-key-auth';

        $digest1 = DnsDigest::make($token1, $keyAuthorization);
        $digest2 = DnsDigest::make($token2, $keyAuthorization);

        expect($digest1)->not->toBe($digest2);
    });

    it('generated digest should be Base64 URL-safe format', function () {
        $token = 'url-safe-test';
        $keyAuthorization = 'url-safe-key';

        $digest = DnsDigest::make($token, $keyAuthorization);

        // Base64 URL-safe format should not contain +, /, = characters
        expect($digest)->not->toContain('+')
            ->and($digest)->not->toContain('/')
            ->and($digest)->not->toContain('=');
    });

    it('can verify digest length', function () {
        $token = 'length-test';
        $keyAuthorization = 'length-key';

        $digest = DnsDigest::make($token, $keyAuthorization);

        // SHA256 hash Base64 encoded length should be 43 characters (after removing padding)
        expect(strlen($digest))->toBe(43);
    });
});
