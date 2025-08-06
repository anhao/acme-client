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

namespace ALAPI\Acme\Endpoints;

use ALAPI\Acme\Http\ResponseHelper;

class Nonce extends AbstractEndpoint
{
    public function getNew(): string
    {
        $response = $this->client
            ->getHttpClient()
            ->head($this->client->directory()->newNonce());

        return trim(ResponseHelper::getHeaderValue($response, 'replay-nonce', ''));
    }
}
