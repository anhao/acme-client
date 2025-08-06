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

namespace ALAPI\Acme\Management\Certificates;

use ALAPI\Acme\Exceptions\AcmeException;
use ALAPI\Acme\Security\Encoding\Base64;
use Exception;

class ARICertID
{
    /**
     * Construct ARI CertID from certificate PEM content.
     *
     * CertID format: base64url(AKI) + "." + base64url(Serial)
     * AKI = Authority Key Identifier extension
     * Serial = Certificate serial number
     */
    public static function fromCertificate(string $certificatePem): string
    {
        $cert = openssl_x509_read($certificatePem);
        if ($cert === false) {
            throw new AcmeException('Unable to parse certificate PEM.');
        }

        $details = openssl_x509_parse($cert);
        if ($details === false) {
            throw new AcmeException('Unable to parse certificate details.');
        }

        // Get Authority Key Identifier (AKI)
        $aki = self::extractAuthorityKeyIdentifier($details);
        if (empty($aki)) {
            throw new AcmeException('Certificate does not contain Authority Key Identifier extension.');
        }

        // Get certificate serial number
        $serial = self::extractSerialNumber($details);
        if (empty($serial)) {
            throw new AcmeException('Unable to extract certificate serial number.');
        }

        // Construct CertID: base64url(AKI) + "." + base64url(Serial)
        $akiBase64url = Base64::urlSafeEncode($aki);
        $serialBase64url = Base64::urlSafeEncode($serial);

        return $akiBase64url . '.' . $serialBase64url;
    }

    /**
     * Construct ARI CertID from certificate bundle.
     */
    public static function fromCertificateBundle(string $certificateBundle): string
    {
        // Extract first certificate (leaf certificate)
        if (preg_match('/-----BEGIN CERTIFICATE-----[\s\S]+?-----END CERTIFICATE-----/', $certificateBundle, $matches)) {
            return self::fromCertificate($matches[0]);
        }

        throw new AcmeException('No certificate found in bundle.');
    }

    /**
     * Construct ARI CertID from certificate file.
     */
    public static function fromCertificateFile(string $certificateFile): string
    {
        if (! file_exists($certificateFile)) {
            throw new AcmeException("Certificate file not found: {$certificateFile}");
        }

        $content = file_get_contents($certificateFile);
        if ($content === false) {
            throw new AcmeException("Unable to read certificate file: {$certificateFile}");
        }

        return self::fromCertificate($content);
    }

    /**
     * Parse CertID into AKI and Serial components.
     */
    public static function parse(string $certId): array
    {
        if (! str_contains($certId, '.')) {
            throw new AcmeException('Invalid CertID format. Expected: base64url(AKI).base64url(Serial)');
        }

        [$akiBase64url, $serialBase64url] = explode('.', $certId, 2);

        try {
            $aki = Base64::urlSafeDecode($akiBase64url);
            $serial = Base64::urlSafeDecode($serialBase64url);
        } catch (Exception $e) {
            throw new AcmeException('Invalid base64url encoding in CertID: ' . $e->getMessage());
        }

        return [
            'aki' => $aki,
            'serial' => $serial,
            'akiBase64url' => $akiBase64url,
            'serialBase64url' => $serialBase64url,
        ];
    }

    /**
     * Validate CertID format.
     */
    public static function isValid(string $certId): bool
    {
        try {
            self::parse($certId);
            return true;
        } catch (AcmeException) {
            return false;
        }
    }

    /**
     * Extract Authority Key Identifier.
     */
    private static function extractAuthorityKeyIdentifier(array $certDetails): string
    {
        if (! isset($certDetails['extensions'])) {
            throw new AcmeException('Certificate has no extensions.');
        }

        $extensions = $certDetails['extensions'];

        // Find Authority Key Identifier extension
        if (isset($extensions['authorityKeyIdentifier'])) {
            $akiString = $extensions['authorityKeyIdentifier'];

            // AKI format is usually "keyid:XX:XX:XX:..."
            if (preg_match('/([A-Fa-f0-9:]+)/', $akiString, $matches)) {
                $hexString = str_replace(':', '', $matches[1]);
                return hex2bin($hexString);
            }

            // In some cases it might be directly in hex format
            if (preg_match('/^[A-Fa-f0-9]+$/', $akiString)) {
                return hex2bin($akiString);
            }
        }

        throw new AcmeException('Unable to extract Authority Key Identifier from certificate.');
    }

    /**
     * Extract certificate serial number.
     */
    private static function extractSerialNumber(array $certDetails): string
    {
        if (! isset($certDetails['serialNumber'])) {
            throw new AcmeException('Certificate serial number not found.');
        }

        $serialNumber = $certDetails['serialNumber'];

        // Serial number might be hex string, need to convert to binary
        if (is_string($serialNumber)) {
            // Remove possible 0x prefix
            $serialNumber = ltrim($serialNumber, '0x');

            // Ensure even length (add leading 0 if needed)
            if (strlen($serialNumber) % 2 === 1) {
                $serialNumber = '0' . $serialNumber;
            }

            return hex2bin($serialNumber);
        }

        throw new AcmeException('Unable to process certificate serial number.');
    }
}
