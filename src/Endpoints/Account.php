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
use ALAPI\Acme\Exceptions\AcmeAccountException;
use ALAPI\Acme\Http\ResponseHelper;
use ALAPI\Acme\Security\Authentication\ExternalAccountBinding;
use ALAPI\Acme\Security\Keys\JsonWebKey;
use ALAPI\Acme\Security\Keys\JsonWebSignature;
use Psr\Http\Message\ResponseInterface;

class Account extends AbstractEndpoint
{
    public function exists(): bool
    {
        return $this->client->localAccount()->exists();
    }

    /**
     * Create account with EAB support.
     *
     * @param null|string $eabKid EAB Key ID
     * @param null|string $eabHmacKey EAB HMAC Key (Base64 encoded)
     * @param array $contacts Contact information array (e.g.: ['mailto:admin@example.com'])
     * @throws AcmeAccountException
     */
    public function create(?string $eabKid = null, ?string $eabHmacKey = null, array $contacts = []): AccountData
    {
        $payload = [
            'termsOfServiceAgreed' => true,
        ];

        // Add contact information
        if (! empty($contacts)) {
            $payload['contact'] = $contacts;
        }

        // If EAB information is provided, add External Account Binding
        if (! empty($eabKid) && ! empty($eabHmacKey)) {
            if (! ExternalAccountBinding::validate($eabKid, $eabHmacKey)) {
                throw new AcmeAccountException('Invalid EAB credentials provided.');
            }

            // Get account's JWK public key
            $accountJwk = JsonWebKey::compute($this->client->localAccount()->getPrivateKey());

            // Generate EAB signature
            $eabJws = ExternalAccountBinding::generate(
                $eabKid,
                $eabHmacKey,
                $accountJwk,
                $this->client->directory()->newAccount()
            );

            $payload['externalAccountBinding'] = $eabJws;
        }

        $response = $this->postToAccountUrl($payload);

        if (ResponseHelper::isSuccess($response) && ResponseHelper::hasHeader($response, 'location')) {
            return AccountData::fromResponse($response);
        }

        $this->throwError($response, 'Creating account failed');
    }

    public function get(): AccountData
    {
        if (! $this->exists()) {
            throw new AcmeAccountException('Local account keys not found.');
        }

        // Use the newAccountUrl to get the account data based on the key.
        // See https://datatracker.ietf.org/doc/html/rfc8555#section-7.3.1
        $payload = ['onlyReturnExisting' => true];
        $response = $this->postToAccountUrl($payload);

        if (ResponseHelper::getStatusCode($response) === 200) {
            return AccountData::fromResponse($response);
        }

        $this->throwError($response, 'Retrieving account failed');
    }

    protected function throwError(ResponseInterface $response, string $defaultMessage): never
    {
        $body = ResponseHelper::parseBody($response);
        $message = (is_array($body) ? $body['detail'] : null) ?? $defaultMessage;
        $this->logResponse('error', $message, $response);

        throw AcmeAccountException::fromResponse($response, $defaultMessage);
    }

    private function signPayload(array $payload): array
    {
        return JsonWebSignature::generate(
            $payload,
            $this->client->directory()->newAccount(),
            $this->client->nonce()->getNew(),
            $this->client->localAccount()->getPrivateKey(),
        );
    }

    private function postToAccountUrl(array $payload): ResponseInterface
    {
        return $this->client->getHttpClient()->post(
            $this->client->directory()->newAccount(),
            $this->signPayload($payload)
        );
    }
}
