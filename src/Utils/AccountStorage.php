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

namespace ALAPI\Acme\Utils;

use ALAPI\Acme\Accounts\Account;
use ALAPI\Acme\Exceptions\AcmeAccountException;

/**
 * Account storage utility class providing convenient file storage methods.
 */
class AccountStorage
{
    /**
     * Save account keys to files.
     */
    public static function saveToFiles(Account $account, string $directory, string $name = 'account'): void
    {
        $directory = rtrim($directory, '/') . '/';

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new AcmeAccountException(sprintf('Directory "%s" was not created', $directory));
        }

        $privateKeyFile = $directory . $name . '-private.pem';
        $publicKeyFile = $directory . $name . '-public.pem';

        if (file_put_contents($privateKeyFile, $account->getPrivateKey()) === false) {
            throw new AcmeAccountException("Failed to write private key to {$privateKeyFile}");
        }

        if (file_put_contents($publicKeyFile, $account->getPublicKey()) === false) {
            throw new AcmeAccountException("Failed to write public key to {$publicKeyFile}");
        }

        // Set secure file permissions
        chmod($privateKeyFile, 0600);
        chmod($publicKeyFile, 0644);
    }

    /**
     * Load account keys from files.
     */
    public static function loadFromFiles(string $directory, string $name = 'account'): Account
    {
        $directory = rtrim($directory, '/') . '/';
        $privateKeyFile = $directory . $name . '-private.pem';
        $publicKeyFile = $directory . $name . '-public.pem';

        if (! file_exists($privateKeyFile)) {
            throw new AcmeAccountException("Private key file not found: {$privateKeyFile}");
        }

        if (! file_exists($publicKeyFile)) {
            throw new AcmeAccountException("Public key file not found: {$publicKeyFile}");
        }

        $privateKey = file_get_contents($privateKeyFile);
        $publicKey = file_get_contents($publicKeyFile);

        if ($privateKey === false) {
            throw new AcmeAccountException("Failed to read private key from {$privateKeyFile}");
        }

        if ($publicKey === false) {
            throw new AcmeAccountException("Failed to read public key from {$publicKeyFile}");
        }

        return new Account($privateKey, $publicKey);
    }

    /**
     * Check if account files exist.
     */
    public static function exists(string $directory, string $name = 'account'): bool
    {
        $directory = rtrim($directory, '/') . '/';
        $privateKeyFile = $directory . $name . '-private.pem';
        $publicKeyFile = $directory . $name . '-public.pem';

        return file_exists($privateKeyFile) && file_exists($publicKeyFile);
    }

    /**
     * Create new account and save to files.
     */
    public static function createAndSave(
        string $directory,
        string $name = 'account',
        string $keyType = 'ECC',
        mixed $keySize = 'P-384'
    ): Account {
        $account = match (strtoupper($keyType)) {
            'RSA' => Account::createRSA((int) $keySize),
            'ECC', 'EC' => Account::createECC((string) $keySize),
            default => throw new AcmeAccountException("Unsupported key type: {$keyType}")
        };

        self::saveToFiles($account, $directory, $name);

        return $account;
    }

    /**
     * Load or create account.
     */
    public static function loadOrCreate(
        string $directory,
        string $name = 'account',
        string $keyType = 'ECC',
        mixed $keySize = 'P-384'
    ): Account {
        if (self::exists($directory, $name)) {
            return self::loadFromFiles($directory, $name);
        }

        return self::createAndSave($directory, $name, $keyType, $keySize);
    }
}
