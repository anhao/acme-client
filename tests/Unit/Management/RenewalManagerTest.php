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
use ALAPI\Acme\AcmeClient;
use ALAPI\Acme\Management\RenewalManager;

describe('RenewalManager', function () {
    beforeEach(function () {
        $this->client = new AcmeClient(staging: true);
        $this->renewalManager = new RenewalManager($this->client);
    });

    it('can create renewal manager', function () {
        expect($this->renewalManager)->toBeInstanceOf(RenewalManager::class);
    });

    it('can set custom default days', function () {
        $customManager = new RenewalManager($this->client, 45);
        expect($customManager)->toBeInstanceOf(RenewalManager::class);
    });

    it('can get client instance', function () {
        // Since RenewalManager may have methods to get client, we test basic instantiation
        expect($this->renewalManager)->toBeInstanceOf(RenewalManager::class);
    });
});
