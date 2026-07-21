<?php

use App\Exceptions\Oai\BadResumptionTokenException;
use App\Services\Oai\ResumptionToken;
use Illuminate\Support\Carbon;

test('encode/decode roundtrip', function () {
    $payload = ['metadataPrefix' => 'oai_dc', 'set' => 'category:x', 'offset' => 100];
    $token = ResumptionToken::encode($payload);
    $decoded = ResumptionToken::decode($token);

    expect($decoded['metadataPrefix'])->toBe('oai_dc');
    expect($decoded['set'])->toBe('category:x');
    expect($decoded['offset'])->toBe(100);
    expect($decoded)->toHaveKey('expiresAt');
});

test('malformed token rejected', function () {
    ResumptionToken::decode('garbage');
})->throws(BadResumptionTokenException::class);

test('expired token rejected', function () {
    Carbon::setTestNow(Carbon::create(2025, 1, 1, 0));
    $token = ResumptionToken::encode(['metadataPrefix' => 'oai_dc']);
    Carbon::setTestNow(Carbon::create(2025, 1, 3, 0));

    ResumptionToken::decode($token);
})->throws(BadResumptionTokenException::class);
