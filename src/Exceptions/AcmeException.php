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

use ALAPI\Acme\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Base ACME exception according to RFC 8555 Section 6.7.
 *
 * This exception implements the ACME error response format as defined in:
 * https://www.rfc-editor.org/rfc/rfc8555#section-6.7
 */
class AcmeException extends RuntimeException
{
    public const URN_PREFIX = 'urn:ietf:params:acme:error:';

    private ?string $type = null;

    private ?string $detail = null;

    private ?string $instance = null;

    private array $subproblems = [];

    private ?array $identifier = null;

    private ?ResponseInterface $response = null;

    final public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?string $type = null,
        ?string $detail = null,
        ?string $instance = null,
        array $subproblems = [],
        ?array $identifier = null,
        ?ResponseInterface $response = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->type = $type;
        $this->detail = $detail ?? $message;
        $this->instance = $instance;
        $this->subproblems = $subproblems;
        $this->identifier = $identifier;
        $this->response = $response;
    }

    /**
     * Create exception from ACME error response.
     */
    public static function fromResponse(ResponseInterface $response, string $defaultMessage = 'ACME request failed'): static
    {
        $statusCode = ResponseHelper::getStatusCode($response);
        $body = ResponseHelper::parseBody($response);

        if (! is_array($body)) {
            return new static(
                $defaultMessage,
                $statusCode,
                null,
                null,
                $defaultMessage,
                null,
                [],
                null,
                $response
            );
        }

        $type = $body['type'] ?? null;
        $detail = $body['detail'] ?? $defaultMessage;
        $instance = $body['instance'] ?? null;
        $subproblems = $body['subproblems'] ?? [];
        $identifier = $body['identifier'] ?? null;

        // Prefer using detail as exception message
        $message = $detail ?: $defaultMessage;

        return new static(
            $message,
            $statusCode,
            null,
            $type,
            $detail,
            $instance,
            $subproblems,
            $identifier,
            $response
        );
    }

    /**
     * Get the ACME error type (without URN prefix).
     */
    public function getAcmeType(): ?string
    {
        if (! $this->type) {
            return null;
        }

        // Remove URN prefix if present
        if (str_starts_with($this->type, self::URN_PREFIX)) {
            return substr($this->type, strlen(self::URN_PREFIX));
        }

        return $this->type;
    }

    /**
     * Get the full ACME error type URN.
     */
    public function getFullType(): ?string
    {
        return $this->type;
    }

    /**
     * Get the ACME error detail.
     */
    public function getDetail(): ?string
    {
        return $this->detail;
    }

    /**
     * Get the ACME error instance URL.
     */
    public function getInstance(): ?string
    {
        return $this->instance;
    }

    /**
     * Get ACME subproblems array.
     */
    public function getSubproblems(): array
    {
        return $this->subproblems;
    }

    /**
     * Check if error has subproblems.
     */
    public function hasSubproblems(): bool
    {
        return ! empty($this->subproblems);
    }

    /**
     * Get identifier associated with this error.
     */
    public function getIdentifier(): ?array
    {
        return $this->identifier;
    }

    /**
     * Get the HTTP response that caused this exception.
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    /**
     * Get HTTP status code.
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->response ? ResponseHelper::getStatusCode($this->response) : $this->getCode();
    }

    /**
     * Check if this is a specific ACME error type.
     */
    public function isType(string $type): bool
    {
        $acmeType = $this->getAcmeType();
        return $acmeType === $type;
    }

    /**
     * Convert to RFC 7807 problem document format.
     */
    public function toProblemDocument(): array
    {
        $document = [
            'type' => $this->type ?? self::URN_PREFIX . 'serverInternal',
            'detail' => $this->detail ?? $this->getMessage(),
        ];

        if ($this->instance) {
            $document['instance'] = $this->instance;
        }

        if ($this->hasSubproblems()) {
            $document['subproblems'] = $this->subproblems;
        }

        if ($this->identifier) {
            $document['identifier'] = $this->identifier;
        }

        return $document;
    }
}
