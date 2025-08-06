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
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Http\Message\ResponseInterface;

class RenewalInfoData extends AbstractData
{
    public function __construct(
        public DateTimeInterface $suggestedWindowStart,
        public DateTimeInterface $suggestedWindowEnd,
        public ?string $explanationUrl = null,
        public array $retryAfter = [],
    ) {
    }

    public static function fromResponse(ResponseInterface $response): RenewalInfoData
    {
        $body = ResponseHelper::getJsonBody($response);

        // Parse suggested renewal window
        $suggestedWindow = $body['suggestedWindow'] ?? [];

        $startTime = isset($suggestedWindow['start'])
            ? new DateTimeImmutable($suggestedWindow['start'])
            : new DateTimeImmutable();

        $endTime = isset($suggestedWindow['end'])
            ? new DateTimeImmutable($suggestedWindow['end'])
            : new DateTimeImmutable('+30 days');

        return new self(
            suggestedWindowStart: $startTime,
            suggestedWindowEnd: $endTime,
            explanationUrl: $body['explanationUrl'] ?? null,
            retryAfter: $body['retryAfter'] ?? [],
        );
    }

    /**
     * Check if current time is within suggested renewal window.
     */
    public function isInSuggestedWindow(?DateTimeInterface $now = null): bool
    {
        $now = $now ?? new DateTime();

        return $now >= $this->suggestedWindowStart && $now <= $this->suggestedWindowEnd;
    }

    /**
     * Check if should renew immediately.
     */
    public function shouldRenewNow(?DateTimeInterface $now = null): bool
    {
        $now = $now ?? new DateTime();

        // If within suggested window, should renew
        if ($this->isInSuggestedWindow($now)) {
            return true;
        }

        // If already past suggested window, should also renew immediately
        return $now > $this->suggestedWindowEnd;
    }

    /**
     * Calculate suggested renewal time window duration (seconds).
     */
    public function getWindowDurationSeconds(): int
    {
        return $this->suggestedWindowEnd->getTimestamp() - $this->suggestedWindowStart->getTimestamp();
    }

    /**
     * Select a random time within suggested window.
     */
    public function getRandomTimeInWindow(): DateTimeInterface
    {
        $windowDuration = $this->getWindowDurationSeconds();
        $randomOffset = rand(0, $windowDuration);

        return (new DateTimeImmutable())->setTimestamp(
            $this->suggestedWindowStart->getTimestamp() + $randomOffset
        );
    }

    /**
     * Get seconds until suggested renewal window starts.
     */
    public function getSecondsUntilWindowStart(?DateTimeInterface $now = null): int
    {
        $now = $now ?? new DateTime();
        $diff = $this->suggestedWindowStart->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }

    /**
     * Get seconds until suggested renewal window ends.
     */
    public function getSecondsUntilWindowEnd(?DateTimeInterface $now = null): int
    {
        $now = $now ?? new DateTime();
        $diff = $this->suggestedWindowEnd->getTimestamp() - $now->getTimestamp();

        return max(0, $diff);
    }
}
