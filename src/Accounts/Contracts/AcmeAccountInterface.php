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

namespace ALAPI\Acme\Accounts\Contracts;

interface AcmeAccountInterface
{
    public function getPrivateKey(): string;

    public function getPublicKey(): string;

    public function exists(): bool;

    /**
     * Generate new key pair.
     *
     * @param string $keyType Key type: 'RSA', 'ECC', 'EC'
     * @param mixed $keySize RSA key length or ECC curve name
     */
    public function generateNewKeys(string $keyType = 'RSA', mixed $keySize = null): bool;
}
