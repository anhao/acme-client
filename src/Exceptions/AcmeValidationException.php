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

namespace ALAPI\Acme\Exceptions;

/**
 * ACME validation related exceptions.
 *
 * This exception is thrown when ACME validation processes fail,
 * such as domain validation challenges.
 */
class AcmeValidationException extends AcmeException
{
}
