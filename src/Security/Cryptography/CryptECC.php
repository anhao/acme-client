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

namespace ALAPI\Acme\Security\Cryptography;

use ALAPI\Acme\Exceptions\AcmeException;

class CryptECC
{
    // Supported elliptic curve mapping
    private const CURVE_MAP = [
        'P-256' => 'prime256v1',
        'P-384' => 'secp384r1',
        'P-521' => 'secp521r1',
    ];

    /**
     * Generate ECC key pair.
     *
     * @param string $curve Elliptic curve name (P-256, P-384, P-521)
     * @return array{privateKey: string, publicKey: string}
     */
    public static function generate(string $curve = 'P-384'): array
    {
        if (! array_key_exists($curve, self::CURVE_MAP)) {
            throw new AcmeException("Unsupported ECC curve: {$curve}");
        }

        $opensslCurve = self::CURVE_MAP[$curve];

        $pKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => $opensslCurve,
        ]);

        if (! $pKey) {
            throw new AcmeException('ECC keypair generation failed.');
        }

        if (! openssl_pkey_export($pKey, $privateKey)) {
            throw new AcmeException('ECC keypair export failed.');
        }

        $details = openssl_pkey_get_details($pKey);

        return [
            'privateKey' => $privateKey,
            'publicKey' => $details['key'],
        ];
    }

    /**
     * Get list of supported elliptic curves.
     */
    public static function getSupportedCurves(): array
    {
        return array_keys(self::CURVE_MAP);
    }

    /**
     * Get OpenSSL name corresponding to elliptic curve.
     */
    public static function getOpensslCurveName(string $curve): string
    {
        if (! array_key_exists($curve, self::CURVE_MAP)) {
            throw new AcmeException("Unsupported ECC curve: {$curve}");
        }

        return self::CURVE_MAP[$curve];
    }
}
