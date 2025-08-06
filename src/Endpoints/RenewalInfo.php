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

use ALAPI\Acme\Data\Transfer\RenewalInfoData;
use ALAPI\Acme\Exceptions\AcmeException;
use ALAPI\Acme\Http\ResponseHelper;
use ALAPI\Acme\Management\Certificates\ARICertID;

class RenewalInfo extends AbstractEndpoint
{
    /**
     * Get renewal information for the specified certificate.
     *
     * @param string $certId ARI certificate identifier
     */
    public function get(string $certId): RenewalInfoData
    {
        if (! $this->client->directory()->supportsARI()) {
            throw new AcmeException('ACME server does not support ARI (Automatic Renewal Information).');
        }

        if (! ARICertID::isValid($certId)) {
            throw new AcmeException('Invalid ARI CertID format.');
        }

        $renewalInfoUrl = $this->client->directory()->renewalInfo();
        $fullUrl = rtrim($renewalInfoUrl, '/') . '/' . $certId;

        $response = $this->client->getHttpClient()->get($fullUrl);

        if (ResponseHelper::getStatusCode($response) === 200) {
            return RenewalInfoData::fromResponse($response);
        }

        if (ResponseHelper::getStatusCode($response) === 404) {
            throw new AcmeException('Certificate not found or ARI information not available.');
        }

        $this->logResponse('error', 'Failed to get renewal information', $response);
        throw AcmeException::fromResponse($response, 'Failed to get renewal information from ACME server');
    }

    /**
     * Get renewal information from certificate PEM content.
     */
    public function getFromCertificate(string $certificatePem): RenewalInfoData
    {
        $certId = ARICertID::fromCertificate($certificatePem);
        return $this->get($certId);
    }

    /**
     * Get renewal information from certificate file.
     */
    public function getFromCertificateFile(string $certificateFile): RenewalInfoData
    {
        $certId = ARICertID::fromCertificateFile($certificateFile);
        return $this->get($certId);
    }

    /**
     * Get renewal information from certificate bundle.
     */
    public function getFromCertificateBundle(string $certificateBundle): RenewalInfoData
    {
        $certId = ARICertID::fromCertificateBundle($certificateBundle);
        return $this->get($certId);
    }

    /**
     * Check if certificate needs renewal according to ARI recommendation.
     */
    public function shouldRenew(string $certId): bool
    {
        try {
            $renewalInfo = $this->get($certId);
            return $renewalInfo->shouldRenewNow();
        } catch (AcmeException $e) {
            // If unable to get ARI information, fallback to time-based judgment
            $this->client->logger('warning', 'Unable to get ARI information: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if certificate needs renewal according to ARI recommendation (from certificate content).
     */
    public function shouldRenewCertificate(string $certificatePem): bool
    {
        try {
            $renewalInfo = $this->getFromCertificate($certificatePem);
            return $renewalInfo->shouldRenewNow();
        } catch (AcmeException $e) {
            $this->client->logger('warning', 'Unable to get ARI information: ' . $e->getMessage());
            return false;
        }
    }
}
