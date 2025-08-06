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

namespace ALAPI\Acme\Accounts;

use ALAPI\Acme\Accounts\Contracts\AcmeAccountInterface;
use ALAPI\Acme\Exceptions\AcmeException;
use ALAPI\Acme\Security\Cryptography\CryptECC;
use ALAPI\Acme\Security\Cryptography\CryptRSA;
use ALAPI\Acme\Security\Keys\KeyInfo;

class Account implements AcmeAccountInterface
{
    private string $privateKey;

    private string $publicKey;

    public function __construct(
        string $privateKey,
        string $publicKey = ''
    ) {
        $this->privateKey = $privateKey;

        // If no public key provided, extract from private key
        if (empty($publicKey)) {
            $this->publicKey = $this->extractPublicKeyFromPrivate($privateKey);
        } else {
            $this->publicKey = $publicKey;
        }
    }

    /**
     * Create account from private key string.
     */
    public static function fromPrivateKey(string $privateKey): self
    {
        return new self($privateKey);
    }

    /**
     * Create new RSA account.
     */
    public static function createRSA(int $keySize = 2048): self
    {
        $keys = CryptRSA::generate($keySize);
        return new self($keys['privateKey'], $keys['publicKey']);
    }

    /**
     * Create new ECC account.
     */
    public static function createECC(string $curve = 'P-384'): self
    {
        $keys = CryptECC::generate($curve);
        return new self($keys['privateKey'], $keys['publicKey']);
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function exists(): bool
    {
        // For string accounts, consider existing if private key is present
        return ! empty($this->privateKey);
    }

    public function generateNewKeys(string $keyType = 'RSA', mixed $keySize = null): bool
    {
        $keyType = strtoupper($keyType);

        // Set default values
        if ($keySize === null) {
            $keySize = KeyInfo::getDefaultKeySize($keyType);
        }

        // Generate key pair based on key type
        $keys = match ($keyType) {
            'RSA' => CryptRSA::generate((int) $keySize),
            'ECC', 'EC' => CryptECC::generate((string) $keySize),
            default => throw new AcmeException("Unsupported key type: {$keyType}")
        };

        $this->privateKey = $keys['privateKey'];
        $this->publicKey = $keys['publicKey'];

        return true;
    }

    /**
     * Get key type information.
     */
    public function getKeyType(): string
    {
        return KeyInfo::getKeyType($this->privateKey);
    }

    /**
     * Get key size information.
     */
    public function getKeySize(): int|string
    {
        return KeyInfo::getKeySize($this->privateKey);
    }

    /**
     * Get complete key information.
     */
    public function getKeyDetails(): array
    {
        return KeyInfo::getKeyDetails($this->privateKey);
    }

    /**
     * Extract public key from private key.
     */
    private function extractPublicKeyFromPrivate(string $privateKey): string
    {
        $resource = openssl_pkey_get_private($privateKey);

        if ($resource === false) {
            throw new AcmeException('Invalid private key provided.');
        }

        $details = openssl_pkey_get_details($resource);

        if ($details === false || ! isset($details['key'])) {
            throw new AcmeException('Cannot extract public key from private key.');
        }

        return $details['key'];
    }
}
