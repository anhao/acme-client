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
use ALAPI\Acme\Security\Keys\KeyInfo;
use OpenSSLAsymmetricKey;

class OpenSsl
{
    /**
     * Generate private key (supports RSA and ECC).
     */
    public static function generatePrivateKey(string $keyType = 'RSA', mixed $keySize = null): OpenSSLAsymmetricKey
    {
        $keyType = strtoupper($keyType);

        // Set default values
        if ($keySize === null) {
            $keySize = KeyInfo::getDefaultKeySize($keyType);
        }

        $config = match ($keyType) {
            'RSA' => [
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => (int) $keySize,
                'digest_alg' => 'sha256',
            ],
            'ECC', 'EC' => [
                'private_key_type' => OPENSSL_KEYTYPE_EC,
                'curve_name' => CryptECC::getOpensslCurveName((string) $keySize),
                'digest_alg' => 'sha256',
            ],
            default => throw new AcmeException("Unsupported key type: {$keyType}")
        };

        $key = openssl_pkey_new($config);

        if ($key === false) {
            throw new AcmeException("Failed to generate {$keyType} private key.");
        }

        return $key;
    }

    /**
     * Generate RSA private key (backward compatibility).
     */
    public static function generateRSAPrivateKey(int $keySize = 2048): OpenSSLAsymmetricKey
    {
        return self::generatePrivateKey('RSA', $keySize);
    }

    /**
     * Generate ECC private key.
     */
    public static function generateECCPrivateKey(string $curve = 'P-384'): OpenSSLAsymmetricKey
    {
        return self::generatePrivateKey('ECC', $curve);
    }

    public static function openSslKeyToString(OpenSSLAsymmetricKey $key): string
    {
        if (! openssl_pkey_export($key, $output)) {
            throw new AcmeException('Exporting SSL key failed.');
        }

        return trim($output);
    }

    public static function generateCsr(array $domains, OpenSSLAsymmetricKey $privateKey): string
    {
        $dn = ['commonName' => $domains[0]];

        $san = implode(',', array_map(function ($dns) {
            return 'DNS:' . $dns;
        }, $domains));

        $tempFile = tmpfile();

        fwrite(
            $tempFile,
            'HOME = .
			RANDFILE = $ENV::HOME/.rnd
			[ req ]
			default_bits = 4096
			default_keyfile = privkey.pem
			distinguished_name = req_distinguished_name
			req_extensions = v3_req
			[ req_distinguished_name ]
			countryName = Country Name (2 letter code)
			[ v3_req ]
			basicConstraints = CA:FALSE
			subjectAltName = ' . $san . '
			keyUsage = nonRepudiation, digitalSignature, keyEncipherment'
        );

        $csr = openssl_csr_new($dn, $privateKey, [
            'digest_alg' => 'sha256',
            'config' => stream_get_meta_data($tempFile)['uri'],
        ]);

        fclose($tempFile);

        if (! openssl_csr_export($csr, $out)) {
            throw new AcmeException('Exporting CSR failed.');
        }

        return trim($out);
    }
}
