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
use ALAPI\Acme\Security\Encoding\Base64;

describe('Base64', function () {
    it('can perform URL-safe encoding', function () {
        $input = 'Hello World!';
        $encoded = Base64::urlSafeEncode($input);

        expect($encoded)->toBe('SGVsbG8gV29ybGQh')
            ->and($encoded)->not->toContain('+')
            ->and($encoded)->not->toContain('/')
            ->and($encoded)->not->toContain('=');
    });

    it('can perform URL-safe decoding', function () {
        $encoded = 'SGVsbG8gV29ybGQh';
        $decoded = Base64::urlSafeDecode($encoded);

        expect($decoded)->toBe('Hello World!');
    });

    it('encoding and decoding is reversible', function () {
        $original = 'This is a test string with special chars: +/=';
        $encoded = Base64::urlSafeEncode($original);
        $decoded = Base64::urlSafeDecode($encoded);

        expect($decoded)->toBe($original);
    });

    it('handles empty string', function () {
        $encoded = Base64::urlSafeEncode('');
        $decoded = Base64::urlSafeDecode('');

        expect($encoded)->toBe('')
            ->and($decoded)->toBe('');
    });

    it('handles binary data', function () {
        $binaryData = random_bytes(32);
        $encoded = Base64::urlSafeEncode($binaryData);
        $decoded = Base64::urlSafeDecode($encoded);

        expect($decoded)->toBe($binaryData)
            ->and($encoded)->not->toContain('+')
            ->and($encoded)->not->toContain('/')
            ->and($encoded)->not->toContain('=');
    });

    it('handles long strings', function () {
        $longString = str_repeat('A very long string that needs to be encoded. ', 100);
        $encoded = Base64::urlSafeEncode($longString);
        $decoded = Base64::urlSafeDecode($encoded);

        expect($decoded)->toBe($longString);
    });
});
