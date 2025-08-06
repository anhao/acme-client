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

namespace ALAPI\Acme\Security\Authentication;

use ALAPI\Acme\Exceptions\AcmeException;
use ALAPI\Acme\Security\Encoding\Base64;

class ExternalAccountBinding
{
    /**
     * Generate EAB JWS signature.
     *
     * @param string $eabKid EAB Key ID
     * @param string $eabHmacKey EAB HMAC Key (Base64 encoded)
     * @param array $accountJwk Account's JWK public key
     * @param string $newAccountUrl ACME server's newAccount URL
     * @return array EAB JWS object
     */
    public static function generate(
        string $eabKid,
        string $eabHmacKey,
        array $accountJwk,
        string $newAccountUrl
    ): array {
        // Validate parameters
        if (empty($eabKid)) {
            throw new AcmeException('EAB Key ID cannot be empty.');
        }

        if (empty($eabHmacKey)) {
            throw new AcmeException('EAB HMAC Key cannot be empty.');
        }

        if (empty($accountJwk)) {
            throw new AcmeException('Account JWK cannot be empty.');
        }

        if (empty($newAccountUrl)) {
            throw new AcmeException('New Account URL cannot be empty.');
        }

        // Construct protected header
        $protected = [
            'alg' => 'HS256',
            'kid' => $eabKid,
            'url' => $newAccountUrl,
        ];

        // Construct payload (account's JWK)
        $payload = $accountJwk;

        // Base64URL encoding
        $protected64 = Base64::urlSafeEncode(json_encode($protected, JSON_UNESCAPED_SLASHES));
        $payload64 = Base64::urlSafeEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        // Generate HMAC signature
        $signatureInput = $protected64 . '.' . $payload64;

        $signature = hash_hmac('sha256', $signatureInput, Base64::urlSafeDecode($eabHmacKey), true);
        $signature64 = Base64::urlSafeEncode($signature);

        return [
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $signature64,
        ];
    }

    /**
     * Validate if EAB configuration is valid.
     */
    public static function validate(string $eabKid, string $eabHmacKey): bool
    {
        if (empty($eabKid) || empty($eabHmacKey)) {
            return false;
        }

        // Validate if HMAC key is valid URL-safe Base64 encoding
        $decoded = Base64::urlSafeDecode($eabHmacKey, true);
        if ($decoded === false) {
            return false;
        }

        // Validate if decoded length is reasonable (usually at least 16 bytes)
        if (strlen($decoded) < 16) {
            return false;
        }

        return true;
    }
}
