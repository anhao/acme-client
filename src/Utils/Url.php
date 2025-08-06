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

class Url
{
    public static function extractId(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $url = rtrim($url, '/');

        $positionLastSlash = strrpos($url, '/');

        return substr($url, $positionLastSlash + 1);
    }
}
