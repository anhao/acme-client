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

use ALAPI\Acme\Data\Transfer\AccountData;
use ALAPI\Acme\Data\Transfer\OrderData;
use ALAPI\Acme\Exceptions\AcmeOrderException;
use ALAPI\Acme\Exceptions\AcmeRateLimitException;
use ALAPI\Acme\Exceptions\OrderNotFoundException;
use ALAPI\Acme\Http\ResponseHelper;
use ALAPI\Acme\Management\Certificates\ARICertID;
use ALAPI\Acme\Security\Encoding\Base64;

class Order extends AbstractEndpoint
{
    /**
     * Create new order.
     *
     * @param AccountData $accountData Account data
     * @param array $domains Domain list
     * @param null|string $replacesCertId Certificate ID to replace (for ARI renewal)
     */
    public function new(AccountData $accountData, array $domains, ?string $replacesCertId = null): OrderData
    {
        $identifiers = [];
        foreach ($domains as $domain) {
            if (preg_match_all('~(\*\.)~', $domain) > 1) {
                throw new AcmeOrderException('Cannot create orders with multiple wildcards in one domain.');
            }

            $identifiers[] = [
                'type' => 'dns',
                'value' => $domain,
            ];
        }

        $payload = [
            'identifiers' => $identifiers,
            'notBefore' => '',
            'notAfter' => '',
        ];

        // If replacesCertId is provided, add to payload to support ARI
        if (! empty($replacesCertId) && $this->client->directory()->supportsARI()) {
            if (! ARICertID::isValid($replacesCertId)) {
                throw new AcmeOrderException('Invalid replaces certificate ID format.');
            }
            $payload['replaces'] = $replacesCertId;

            $this->client->logger('info', "Creating ARI renewal order to replace certificate: {$replacesCertId}");
        }

        $newOrderUrl = $this->client->directory()->newOrder();

        $keyId = $this->createKeyId(
            $accountData->url,
            $this->client->directory()->newOrder(),
            $payload
        );

        $response = $this->client->getHttpClient()->post($newOrderUrl, $keyId);

        if (ResponseHelper::getStatusCode($response) === 201) {
            return OrderData::fromResponse($response, $accountData->url, $newOrderUrl);
        }

        $this->logResponse('error', 'Creating new order failed; bad response code.', $response, ['payload' => $payload, 'keyId' => $keyId]);

        throw AcmeOrderException::fromResponse($response, 'Creating new order failed; bad response code.');
    }

    /**
     * Create ARI renewal order.
     *
     * @param AccountData $accountData Account data
     * @param array $domains Domain list
     * @param string $certificatePem Certificate PEM content to renew
     */
    public function newRenewal(AccountData $accountData, array $domains, string $certificatePem): OrderData
    {
        $replacesCertId = ARICertID::fromCertificate($certificatePem);
        return $this->new($accountData, $domains, $replacesCertId);
    }

    /**
     * Create ARI renewal order from certificate file.
     */
    public function newRenewalFromFile(AccountData $accountData, array $domains, string $certificateFile): OrderData
    {
        $replacesCertId = ARICertID::fromCertificateFile($certificateFile);
        return $this->new($accountData, $domains, $replacesCertId);
    }

    /**
     * Create ARI renewal order from certificate bundle.
     */
    public function newRenewalFromBundle(AccountData $accountData, array $domains, string $certificateBundle): OrderData
    {
        $replacesCertId = ARICertID::fromCertificateBundle($certificateBundle);
        return $this->new($accountData, $domains, $replacesCertId);
    }

    public function get(string $id): OrderData
    {
        $account = $this->client->account()->get();

        $orderUrl = sprintf(
            '%s%s/%s',
            $this->client->directory()->getOrder(),
            $account->id,
            $id,
        );

        $response = $this->client->getHttpClient()->get($orderUrl);

        // Everything below 400 is a success.
        if (ResponseHelper::getStatusCode($response) < 400) {
            return OrderData::fromResponse($response, $account->url, $orderUrl);
        }

        // Always log the error.
        $this->logResponse('error', 'Getting order failed; bad response code.', $response);

        $body = ResponseHelper::parseBody($response);
        $errorDetail = is_array($body) ? ($body['detail'] ?? 'Unknown error') : 'Unknown error';

        match (ResponseHelper::getStatusCode($response)) {
            404 => throw OrderNotFoundException::fromResponse($response, $errorDetail),
            429 => throw AcmeRateLimitException::fromResponse($response, $errorDetail),
            default => throw AcmeOrderException::fromResponse($response, $errorDetail),
        };
    }

    public function finalize(OrderData $orderData, string $csr): bool
    {
        if (! $orderData->isReady()) {
            $this->client->logger(
                'error',
                "Order status for {$orderData->id} is {$orderData->status}. Cannot finalize order."
            );

            return false;
        }

        if (preg_match('~-----BEGIN\sCERTIFICATE\sREQUEST-----(.*)-----END\sCERTIFICATE\sREQUEST-----~s', $csr, $matches)) {
            $csr = $matches[1];
        }

        $csr = trim(Base64::urlSafeEncode(base64_decode($csr)));

        $signedPayload = $this->createKeyId(
            $orderData->accountUrl,
            $orderData->finalizeUrl,
            compact('csr')
        );

        $response = $this->client->getHttpClient()->post($orderData->finalizeUrl, $signedPayload);

        if (ResponseHelper::getStatusCode($response) === 200) {
            $body = ResponseHelper::getJsonBody($response);
            if (isset($body['certificate'])) {
                $orderData->setCertificateUrl($body['certificate']);
            }

            // If this is ARI renewal, log it
            if ($orderData->isARIRenewal()) {
                $this->client->logger('info', "ARI renewal order {$orderData->id} finalized successfully. Replaced: {$orderData->getReplacedCertificateId()}");
            }

            return true;
        }

        $this->logResponse('error', 'Cannot finalize order ' . $orderData->id, $response, ['orderData' => $orderData]);

        return false;
    }
}
