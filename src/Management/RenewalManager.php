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

namespace ALAPI\Acme\Management;

use ALAPI\Acme\AcmeClient;
use ALAPI\Acme\Data\Transfer\RenewalInfoData;
use ALAPI\Acme\Exceptions\AcmeException;
use DateTime;
use DateTimeInterface;

class RenewalManager
{
    public function __construct(
        private AcmeClient $client,
        private int $defaultRenewalDays = 30
    ) {
    }

    /**
     * Check if certificate needs renewal.
     * Prioritize ARI, fallback to time-based judgment.
     */
    public function shouldRenew(string $certificatePem, ?int $renewalDays = null): bool
    {
        $renewalDays = $renewalDays ?? $this->defaultRenewalDays;

        // If ARI is supported, prioritize ARI judgment
        if ($this->client->directory()->supportsARI()) {
            try {
                $renewalInfo = $this->client->renewalInfo()->getFromCertificate($certificatePem);

                // If within ARI suggested window, should renew
                if ($renewalInfo->shouldRenewNow()) {
                    $this->client->logger('info', 'ARI suggests renewal should happen now');
                    return true;
                }

                // If not yet at ARI suggested time, no need to renew
                if (! $renewalInfo->isInSuggestedWindow()) {
                    $this->client->logger('info', 'ARI suggests renewal is not yet needed');
                    return false;
                }
            } catch (AcmeException $e) {
                $this->client->logger('warning', 'Failed to get ARI information, falling back to time-based renewal: ' . $e->getMessage());
            }
        }

        // Fallback to certificate expiration time based judgment
        return $this->shouldRenewByExpiration($certificatePem, $renewalDays);
    }

    /**
     * Determine if renewal is needed based on certificate expiration time.
     */
    public function shouldRenewByExpiration(string $certificatePem, int $renewalDays = 30): bool
    {
        $cert = openssl_x509_read($certificatePem);
        if ($cert === false) {
            throw new AcmeException('Unable to parse certificate.');
        }

        $details = openssl_x509_parse($cert);
        if ($details === false) {
            throw new AcmeException('Unable to parse certificate details.');
        }

        $validTo = $details['validTo_time_t'];
        $now = time();
        $renewalTime = $validTo - ($renewalDays * 24 * 60 * 60);

        return $now >= $renewalTime;
    }

    /**
     * Get renewal information (ARI or time-based).
     */
    public function getRenewalInfo(string $certificatePem): array
    {
        $info = [
            'type' => 'time-based',
            'shouldRenew' => false,
            'expiresAt' => null,
            'renewalTime' => null,
        ];

        // Parse certificate expiration time
        $cert = openssl_x509_read($certificatePem);
        if ($cert !== false) {
            $details = openssl_x509_parse($cert);
            if ($details !== false) {
                $info['expiresAt'] = (new DateTime())->setTimestamp($details['validTo_time_t']);
                $info['renewalTime'] = (new DateTime())->setTimestamp($details['validTo_time_t'] - ($this->defaultRenewalDays * 24 * 60 * 60));
            }
        }

        // If ARI is supported, get ARI information
        if ($this->client->directory()->supportsARI()) {
            try {
                $renewalInfo = $this->client->renewalInfo()->getFromCertificate($certificatePem);

                $info['type'] = 'ari';
                $info['ariInfo'] = [
                    'suggestedWindowStart' => $renewalInfo->suggestedWindowStart,
                    'suggestedWindowEnd' => $renewalInfo->suggestedWindowEnd,
                    'explanationUrl' => $renewalInfo->explanationUrl,
                    'isInWindow' => $renewalInfo->isInSuggestedWindow(),
                    'shouldRenowNow' => $renewalInfo->shouldRenewNow(),
                ];

                $info['shouldRenew'] = $renewalInfo->shouldRenewNow();
                $info['renewalTime'] = $renewalInfo->suggestedWindowStart;
            } catch (AcmeException $e) {
                $this->client->logger('warning', 'Failed to get ARI information: ' . $e->getMessage());
                $info['shouldRenew'] = $this->shouldRenewByExpiration($certificatePem);
            }
        } else {
            $info['shouldRenew'] = $this->shouldRenewByExpiration($certificatePem);
        }

        return $info;
    }

    /**
     * Select optimal renewal time.
     *
     * @param string $certificatePem Certificate PEM content
     * @param int $maxSleepHours Maximum hours willing to wait
     */
    public function selectRenewalTime(string $certificatePem, int $maxSleepHours = 24): ?DateTimeInterface
    {
        if (! $this->client->directory()->supportsARI()) {
            return null; // Return null when ARI not supported, indicating immediate renewal
        }

        try {
            $renewalInfo = $this->client->renewalInfo()->getFromCertificate($certificatePem);
            return $this->selectTimeFromARIWindow($renewalInfo, $maxSleepHours);
        } catch (AcmeException $e) {
            $this->client->logger('warning', 'Failed to get ARI information for time selection: ' . $e->getMessage());
            return null; // Fallback to immediate renewal
        }
    }

    /**
     * Set default renewal days.
     */
    public function setDefaultRenewalDays(int $days): self
    {
        $this->defaultRenewalDays = $days;
        return $this;
    }

    /**
     * Select renewal time based on ARI window.
     * Implements algorithm suggested in Let's Encrypt documentation.
     */
    private function selectTimeFromARIWindow(RenewalInfoData $renewalInfo, int $maxSleepHours): ?DateTimeInterface
    {
        $now = new DateTime();
        $start = $renewalInfo->suggestedWindowStart;
        $end = $renewalInfo->suggestedWindowEnd;

        // If current time has passed suggested window, renew immediately
        if ($now > $end) {
            return $now;
        }

        // Select a random time within suggested window
        $windowDuration = $end->getTimestamp() - $start->getTimestamp();
        $randomOffset = rand(0, $windowDuration);
        $selectedTime = (new DateTime())->setTimestamp($start->getTimestamp() + $randomOffset);

        // If selected time has passed, renew immediately
        if ($selectedTime < $now) {
            return $now;
        }

        // If client is willing to wait until selected time, return that time
        $maxWaitTime = $now->modify("+{$maxSleepHours} hours");
        if ($selectedTime <= $maxWaitTime) {
            return $selectedTime;
        }

        // Otherwise return null, indicating wait until normal wake time
        return null;
    }
}
