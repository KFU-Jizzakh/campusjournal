<?php

use App\Support\CrossrefConfig;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Cache::forget('crossref_crossmark_misconfigured_warned');
    config(['services.crossref.crossmark.policy_doi_misconfigured' => true]);
});

afterEach(function () {
    Cache::forget('crossref_crossmark_misconfigured_warned');
    config(['services.crossref.crossmark.policy_doi_misconfigured' => false]);
});

test('warnIfMisconfigured logs once when the policy DOI is misconfigured', function () {
    $logManager = Log::getFacadeRoot();
    Log::spy();

    try {
        CrossrefConfig::warnIfMisconfigured();
        CrossrefConfig::warnIfMisconfigured();

        Log::shouldHaveReceived('warning')->once();
    } finally {
        Log::swap($logManager);
    }
});

test('warnIfMisconfigured is silent for an empty value', function () {
    config(['services.crossref.crossmark.policy_doi_misconfigured' => false]);

    $logManager = Log::getFacadeRoot();
    Log::spy();

    try {
        CrossrefConfig::warnIfMisconfigured();

        Log::shouldNotHaveReceived('warning');
    } finally {
        Log::swap($logManager);
    }
});

test('warnIfMisconfigured still warns and does not throw when the cache store fails', function () {
    Cache::shouldReceive('forget')->andReturn(true);
    Cache::shouldReceive('add')->andThrow(new RuntimeException('cache store is down'));

    $logManager = Log::getFacadeRoot();
    Log::spy();

    try {
        CrossrefConfig::warnIfMisconfigured();

        Log::shouldHaveReceived('warning')->once();
    } finally {
        Log::swap($logManager);
    }
});
