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

namespace ALAPI\Acme\Validation\Challenges;

use ALAPI\Acme\Security\Encoding\Base64;

class DnsDigest
{
    public static function createHash(string $token, string $thumbprint): string
    {
        return hash(
            'sha256',
            sprintf('%s.%s', $token, $thumbprint),
            true
        );
    }

    public static function make(string $token, string $thumbprint): string
    {
        return Base64::urlSafeEncode(self::createHash($token, $thumbprint));
    }
}
