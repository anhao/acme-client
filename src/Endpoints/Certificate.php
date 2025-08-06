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

use ALAPI\Acme\Data\Transfer\CertificateBundleData;
use ALAPI\Acme\Data\Transfer\OrderData;
use ALAPI\Acme\Exceptions\AcmeCertificateException;
use ALAPI\Acme\Http\ResponseHelper;
use ALAPI\Acme\Security\Encoding\Base64;

class Certificate extends AbstractEndpoint
{
    public function getBundle(OrderData $orderData): CertificateBundleData
    {
        $signedPayload = $this->createKeyId($orderData->accountUrl, $orderData->certificateUrl);

        $response = $this->client->getHttpClient()->post($orderData->certificateUrl, $signedPayload);

        if (! ResponseHelper::isSuccess($response)) {
            $this->logResponse('error', 'Failed to fetch certificate', $response);

            throw AcmeCertificateException::fromResponse($response, 'Failed to fetch certificate');
        }

        return CertificateBundleData::fromResponse($response);
    }

    public function revoke(string $pem, int $reason = 0): bool
    {
        if (($data = openssl_x509_read($pem)) === false) {
            throw new AcmeCertificateException('Could not parse the certificate.');
        }

        if (openssl_x509_export($data, $certificate) === false) {
            throw new AcmeCertificateException('Could not export the certificate.');
        }

        preg_match('~-----BEGIN\sCERTIFICATE-----(.*)-----END\sCERTIFICATE-----~s', $certificate, $matches);
        $certificate = trim(Base64::urlSafeEncode(base64_decode(trim($matches[1]))));

        $revokeUrl = $this->client->directory()->revoke();

        $signedPayload = $this->createKeyId(
            $this->client->account()->get()->url,
            $revokeUrl,
            [
                'certificate' => $certificate,
                'reason' => $reason,
            ]
        );

        $response = $this->client->getHttpClient()->post($revokeUrl, $signedPayload);

        if (! ResponseHelper::isSuccess($response)) {
            $this->logResponse('error', 'Failed to revoke certificate', $response);
        }

        return ResponseHelper::isSuccess($response);
    }
}
