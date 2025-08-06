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

namespace ALAPI\Acme\Security\Keys;

use ALAPI\Acme\Exceptions\AcmeException;
use ALAPI\Acme\Security\Encoding\Base64;
use SensitiveParameter;

class JsonWebKey
{
    /**
     * Calculate JWK representation of key, supports RSA and ECC keys.
     */
    public static function compute(
        #[SensitiveParameter]
        string $accountKey
    ): array {
        $privateKey = openssl_pkey_get_private($accountKey);

        if ($privateKey === false) {
            throw new AcmeException('Can not create private key.');
        }

        $details = openssl_pkey_get_details($privateKey);

        // Detect key type
        $keyType = $details['type'] ?? null;

        switch ($keyType) {
            case OPENSSL_KEYTYPE_RSA:
                return self::computeRSA($details);
            case OPENSSL_KEYTYPE_EC:
                return self::computeECC($details);
            default:
                throw new AcmeException('Unsupported key type for JWK computation.');
        }
    }

    /**
     * Calculate JWK thumbprint.
     */
    public static function thumbprint(array $jwk): string
    {
        // According to RFC 7517, keys must be sorted alphabetically when calculating thumbprint
        ksort($jwk);
        return Base64::urlSafeEncode(hash('sha256', json_encode($jwk, JSON_UNESCAPED_SLASHES), true));
    }

    /**
     * Calculate JWK representation of RSA key.
     */
    private static function computeRSA(array $details): array
    {
        return [
            'e' => Base64::urlSafeEncode($details['rsa']['e']),
            'kty' => 'RSA',
            'n' => Base64::urlSafeEncode($details['rsa']['n']),
        ];
    }

    /**
     * Calculate JWK representation of ECC key.
     */
    private static function computeECC(array $details): array
    {
        $curve = $details['ec']['curve_name'] ?? '';

        // Map OpenSSL curve names to JWK standard names
        $jwkCurve = match ($curve) {
            'prime256v1' => 'P-256',
            'secp384r1' => 'P-384',
            'secp521r1' => 'P-521',
            default => throw new AcmeException("Unsupported ECC curve: {$curve}")
        };

        // Get coordinate points
        $x = $details['ec']['x'] ?? '';
        $y = $details['ec']['y'] ?? '';

        if (empty($x) || empty($y)) {
            throw new AcmeException('Invalid ECC key details.');
        }

        return [
            'kty' => 'EC',
            'crv' => $jwkCurve,
            'x' => Base64::urlSafeEncode($x),
            'y' => Base64::urlSafeEncode($y),
        ];
    }
}
