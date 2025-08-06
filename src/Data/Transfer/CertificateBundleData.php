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

namespace ALAPI\Acme\Data\Transfer;

use ALAPI\Acme\Data\AbstractData;
use ALAPI\Acme\Http\ResponseHelper;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;

class CertificateBundleData extends AbstractData
{
    public function __construct(
        public string $fullchain,
        public string $certificate,
        public string $intermediate,
    ) {
    }

    public static function fromResponse(ResponseInterface $response): CertificateBundleData
    {
        $body = ResponseHelper::parseBody($response);

        // Certificate content should be string, not JSON
        if (is_array($body)) {
            throw new InvalidArgumentException('Certificate response should be string, not JSON');
        }

        [$fullchain, $certificate, $intermediate] = self::splitBundle($body);

        return new self(
            fullchain: $fullchain,
            certificate: $certificate,
            intermediate: $intermediate,
        );
    }

    public static function splitBundle(string $bundle): array
    {
        $certificates = [];
        $currentCert = '';

        foreach (explode("\n", $bundle) as $line) {
            if (str_contains($line, '-----BEGIN CERTIFICATE-----')) {
                $currentCert = $line . "\n";
            } elseif (str_contains($line, '-----END CERTIFICATE-----')) {
                $currentCert .= $line . "\n";
                $certificates[] = trim($currentCert);
                $currentCert = '';
            } else {
                $currentCert .= $line . "\n";
            }
        }

        $fullchain = implode("\n", $certificates);
        $certificate = $certificates[0] ?? '';
        $intermediate = implode('', array_slice($certificates, 1));

        return [$fullchain, $certificate, $intermediate];
    }
}
