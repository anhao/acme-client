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

class Thumbprint
{
    public static function make(string $accountKey): string
    {
        return JsonWebKey::thumbprint(JsonWebKey::compute($accountKey));
    }
}
