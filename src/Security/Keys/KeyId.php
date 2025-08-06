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

class KeyId
{
    public static function generate(
        #[SensitiveParameter]
        string $accountPrivateKey,
        string $kid,
        string $url,
        string $nonce,
        ?array $payload = null
    ): array {
        $privateKey = openssl_pkey_get_private($accountPrivateKey);

        if ($privateKey === false) {
            throw new AcmeException('Cannot load private key for KeyId generation.');
        }

        // Detect key type and choose appropriate algorithm
        $details = openssl_pkey_get_details($privateKey);
        $keyType = $details['type'] ?? null;

        $algorithm = match ($keyType) {
            OPENSSL_KEYTYPE_RSA => 'RS256',
            OPENSSL_KEYTYPE_EC => self::getECCAlgorithm($details),
            default => throw new AcmeException('Unsupported key type for KeyId generation.')
        };

        $data = [
            'alg' => $algorithm,
            'kid' => $kid,
            'nonce' => $nonce,
            'url' => $url,
        ];

        $payload = is_array($payload)
            ? json_encode($payload, JSON_UNESCAPED_SLASHES)
            : '';

        $payload64 = Base64::urlSafeEncode($payload);
        $protected64 = Base64::urlSafeEncode(json_encode($data, JSON_UNESCAPED_SLASHES));

        // Choose signing algorithm based on key type
        $opensslAlgorithm = match ($algorithm) {
            'RS256' => 'SHA256',
            'ES256' => 'SHA256',
            'ES384' => 'SHA384',
            'ES512' => 'SHA512',
            default => throw new AcmeException("Unsupported signing algorithm: {$algorithm}")
        };

        $success = openssl_sign(
            $protected64 . '.' . $payload64,
            $signed,
            $privateKey,
            $opensslAlgorithm
        );

        if (! $success) {
            throw new AcmeException('KeyId signing failed.');
        }

        // ECC signatures need special handling
        if ($keyType === OPENSSL_KEYTYPE_EC) {
            $signed = self::convertECCSignature($signed, $details);
        }

        $signed64 = Base64::urlSafeEncode($signed);

        return [
            'protected' => $protected64,
            'payload' => $payload64,
            'signature' => $signed64,
        ];
    }

    /**
     * Choose signing algorithm based on ECC curve.
     */
    private static function getECCAlgorithm(array $keyDetails): string
    {
        $curve = $keyDetails['ec']['curve_name'] ?? '';

        return match ($curve) {
            'prime256v1' => 'ES256',  // P-256
            'secp384r1' => 'ES384',   // P-384
            'secp521r1' => 'ES512',   // P-521
            default => throw new AcmeException("Unsupported ECC curve for signing: {$curve}")
        };
    }

    /**
     * Convert OpenSSL ECC signature format to JWS format.
     */
    private static function convertECCSignature(string $signature, array $keyDetails): string
    {
        $curve = $keyDetails['ec']['curve_name'] ?? '';

        // Determine coordinate length based on curve
        $coordinateLength = match ($curve) {
            'prime256v1' => 32,  // P-256: 32 bytes each for r and s
            'secp384r1' => 48,   // P-384: 48 bytes each for r and s
            'secp521r1' => 66,   // P-521: 66 bytes each for r and s
            default => throw new AcmeException("Unsupported ECC curve: {$curve}")
        };

        // Parse DER format signature
        $offset = 0;

        // Check SEQUENCE marker
        if (ord($signature[$offset++]) !== 0x30) {
            throw new AcmeException('Invalid ECC signature format.');
        }

        // Skip length bytes
        $length = ord($signature[$offset++]);
        if ($length & 0x80) {
            $offset += $length & 0x7F;
        }

        // Read r value
        if (ord($signature[$offset++]) !== 0x02) {
            throw new AcmeException('Invalid ECC signature format for r.');
        }

        $rLength = ord($signature[$offset++]);
        $r = substr($signature, $offset, $rLength);
        $offset += $rLength;

        // Read s value
        if (ord($signature[$offset++]) !== 0x02) {
            throw new AcmeException('Invalid ECC signature format for s.');
        }

        $sLength = ord($signature[$offset++]);
        $s = substr($signature, $offset, $sLength);

        // Pad to fixed length
        $r = str_pad(ltrim($r, "\x00"), $coordinateLength, "\x00", STR_PAD_LEFT);
        $s = str_pad(ltrim($s, "\x00"), $coordinateLength, "\x00", STR_PAD_LEFT);

        return $r . $s;
    }
}
