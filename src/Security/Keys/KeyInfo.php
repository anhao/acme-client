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

class KeyInfo
{
    /**
     * Get key type.
     */
    public static function getKeyType(string $privateKey): string
    {
        $resource = openssl_pkey_get_private($privateKey);

        if ($resource === false) {
            throw new AcmeException('Invalid private key.');
        }

        $details = openssl_pkey_get_details($resource);
        $keyType = $details['type'] ?? null;

        return match ($keyType) {
            OPENSSL_KEYTYPE_RSA => 'RSA',
            OPENSSL_KEYTYPE_EC => 'ECC',
            default => 'Unknown'
        };
    }

    /**
     * Get key size information.
     */
    public static function getKeySize(string $privateKey): int|string
    {
        $resource = openssl_pkey_get_private($privateKey);

        if ($resource === false) {
            throw new AcmeException('Invalid private key.');
        }

        $details = openssl_pkey_get_details($resource);
        $keyType = $details['type'] ?? null;

        return match ($keyType) {
            OPENSSL_KEYTYPE_RSA => $details['bits'] ?? 0,
            OPENSSL_KEYTYPE_EC => $details['ec']['curve_name'] ?? 'unknown',
            default => 0
        };
    }

    /**
     * Get complete key information.
     */
    public static function getKeyDetails(string $privateKey): array
    {
        $resource = openssl_pkey_get_private($privateKey);

        if ($resource === false) {
            throw new AcmeException('Invalid private key.');
        }

        $details = openssl_pkey_get_details($resource);
        $keyType = $details['type'] ?? null;

        $info = [
            'type' => match ($keyType) {
                OPENSSL_KEYTYPE_RSA => 'RSA',
                OPENSSL_KEYTYPE_EC => 'ECC',
                default => 'Unknown'
            },
            'size' => match ($keyType) {
                OPENSSL_KEYTYPE_RSA => $details['bits'] ?? 0,
                OPENSSL_KEYTYPE_EC => $details['ec']['curve_name'] ?? 'unknown',
                default => 0
            },
            'opensslType' => $keyType,
            'details' => $details,
        ];

        return $info;
    }

    /**
     * Set default values for key generation.
     */
    public static function getDefaultKeySize(string $keyType): mixed
    {
        $keyType = strtoupper($keyType);

        return match ($keyType) {
            'RSA' => 2048,
            'ECC', 'EC' => 'P-256',
            default => throw new AcmeException("Unsupported key type: {$keyType}")
        };
    }

    /**
     * Validate if key parameters are valid.
     */
    public static function validateKeyParameters(string $keyType, mixed $keySize): bool
    {
        $keyType = strtoupper($keyType);

        return match ($keyType) {
            'RSA' => is_int($keySize) && in_array($keySize, [2048, 3072, 4096], true),
            'ECC', 'EC' => is_string($keySize) && in_array($keySize, ['P-256', 'P-384', 'P-521'], true),
            default => false
        };
    }
}
