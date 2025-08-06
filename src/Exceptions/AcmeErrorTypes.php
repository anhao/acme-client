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
 * ACME Error Types as defined in RFC 8555 Section 6.7.
 *
 * @see https://www.rfc-editor.org/rfc/rfc8555#section-6.7
 * @see https://www.iana.org/assignments/acme/acme.xhtml#acme-error-types
 */
final class AcmeErrorTypes
{
    /**
     * The request specified an account that does not exist.
     */
    public const ACCOUNT_DOES_NOT_EXIST = 'accountDoesNotExist';

    /**
     * The request specified a certificate to be revoked that has already been revoked.
     */
    public const ALREADY_REVOKED = 'alreadyRevoked';

    /**
     * The CSR is unacceptable (e.g., due to a short key).
     */
    public const BAD_CSR = 'badCSR';

    /**
     * The client sent an unacceptable anti-replay nonce.
     */
    public const BAD_NONCE = 'badNonce';

    /**
     * The JWS was signed by a public key the server does not support.
     */
    public const BAD_PUBLIC_KEY = 'badPublicKey';

    /**
     * The revocation reason provided is not allowed by the server.
     */
    public const BAD_REVOCATION_REASON = 'badRevocationReason';

    /**
     * The JWS was signed with an algorithm the server does not support.
     */
    public const BAD_SIGNATURE_ALGORITHM = 'badSignatureAlgorithm';

    /**
     * Certification Authority Authorization (CAA) records forbid the CA from issuing a certificate.
     */
    public const CAA = 'caa';

    /**
     * Specific error conditions are indicated in the "subproblems" array.
     */
    public const COMPOUND = 'compound';

    /**
     * The server could not connect to validation target.
     */
    public const CONNECTION = 'connection';

    /**
     * There was a problem with a DNS query during identifier validation.
     */
    public const DNS = 'dns';

    /**
     * The request must include a value for the "externalAccountBinding" field.
     */
    public const EXTERNAL_ACCOUNT_REQUIRED = 'externalAccountRequired';

    /**
     * Response received didn't match the challenge's requirements.
     */
    public const INCORRECT_RESPONSE = 'incorrectResponse';

    /**
     * A contact URL for an account was invalid.
     */
    public const INVALID_CONTACT = 'invalidContact';

    /**
     * The request message was malformed.
     */
    public const MALFORMED = 'malformed';

    /**
     * The request attempted to finalize an order that is not ready to be finalized.
     */
    public const ORDER_NOT_READY = 'orderNotReady';

    /**
     * The request exceeds a rate limit.
     */
    public const RATE_LIMITED = 'rateLimited';

    /**
     * The server will not issue certificates for the identifier.
     */
    public const REJECTED_IDENTIFIER = 'rejectedIdentifier';

    /**
     * The server experienced an internal error.
     */
    public const SERVER_INTERNAL = 'serverInternal';

    /**
     * The server received a TLS error during validation.
     */
    public const TLS = 'tls';

    /**
     * The client lacks sufficient authorization.
     */
    public const UNAUTHORIZED = 'unauthorized';

    /**
     * A contact URL for an account used an unsupported protocol scheme.
     */
    public const UNSUPPORTED_CONTACT = 'unsupportedContact';

    /**
     * An identifier is of an unsupported type.
     */
    public const UNSUPPORTED_IDENTIFIER = 'unsupportedIdentifier';

    /**
     * Visit the "instance" URL and take actions specified there.
     */
    public const USER_ACTION_REQUIRED = 'userActionRequired';

    /**
     * Get all defined error types.
     */
    public static function all(): array
    {
        return [
            self::ACCOUNT_DOES_NOT_EXIST,
            self::ALREADY_REVOKED,
            self::BAD_CSR,
            self::BAD_NONCE,
            self::BAD_PUBLIC_KEY,
            self::BAD_REVOCATION_REASON,
            self::BAD_SIGNATURE_ALGORITHM,
            self::CAA,
            self::COMPOUND,
            self::CONNECTION,
            self::DNS,
            self::EXTERNAL_ACCOUNT_REQUIRED,
            self::INCORRECT_RESPONSE,
            self::INVALID_CONTACT,
            self::MALFORMED,
            self::ORDER_NOT_READY,
            self::RATE_LIMITED,
            self::REJECTED_IDENTIFIER,
            self::SERVER_INTERNAL,
            self::TLS,
            self::UNAUTHORIZED,
            self::UNSUPPORTED_CONTACT,
            self::UNSUPPORTED_IDENTIFIER,
            self::USER_ACTION_REQUIRED,
        ];
    }

    /**
     * Check if error type is defined in RFC 8555.
     */
    public static function isValidType(string $type): bool
    {
        return in_array($type, self::all(), true);
    }

    /**
     * Get URN for an error type.
     */
    public static function getUrn(string $type): string
    {
        return AcmeException::URN_PREFIX . $type;
    }
}
