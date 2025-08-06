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
 * Domain validation exception.
 *
 * Thrown when domain validation challenges fail
 */
class DomainValidationException extends AcmeValidationException
{
    public static function localHttpChallengeTestFailed(string $domain, int $code): self
    {
        return new self(sprintf(
            'The local HTTP challenge test for %s received an invalid response with a %s status code.',
            $domain,
            $code
        ));
    }

    public static function localDnsChallengeTestFailed(string $domain): self
    {
        return new self(sprintf(
            "Couldn't fetch the correct DNS records for %s.",
            $domain
        ));
    }
}
