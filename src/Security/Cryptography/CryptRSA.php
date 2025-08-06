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

class CryptRSA
{
    /**
     * Generate RSA key pair.
     *
     * @param int $keySize RSA key length (2048, 3072, 4096)
     * @return array{privateKey: string, publicKey: string}
     */
    public static function generate(int $keySize = 2048): array
    {
        // Validate key length
        if (! in_array($keySize, [2048, 3072, 4096], true)) {
            throw new AcmeException("Invalid RSA key size: {$keySize}. Supported sizes: 1024, 2048, 3072, 4096");
        }

        $pKey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => $keySize,
        ]);

        if (! $pKey) {
            throw new AcmeException('RSA keypair generation failed.');
        }

        if (! openssl_pkey_export($pKey, $privateKey)) {
            throw new AcmeException('RSA keypair export failed.');
        }

        $details = openssl_pkey_get_details($pKey);

        return [
            'privateKey' => $privateKey,
            'publicKey' => $details['key'],
        ];
    }
}
