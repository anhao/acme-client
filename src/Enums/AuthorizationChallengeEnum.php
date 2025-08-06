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

namespace ALAPI\Acme\Enums;

enum AuthorizationChallengeEnum: string
{
    case HTTP = 'http-01';
    case DNS = 'dns-01';
}
