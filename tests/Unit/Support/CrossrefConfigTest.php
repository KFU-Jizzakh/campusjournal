<?php

use App\Support\CrossrefConfig;

test('policyDoi keeps a valid policy DOI', function () {
    expect(CrossrefConfig::policyDoi('10.5555/crossmark-policy'))->toBe('10.5555/crossmark-policy');
    expect(CrossrefConfig::policyDoi('10.123456789/very.long-suffix'))->toBe('10.123456789/very.long-suffix');
});

test('policyDoi rejects URLs and malformed values', function (string $value) {
    expect(CrossrefConfig::policyDoi($value))->toBeNull();
})->with([
    'https://journal.example/crossmark-policy',
    'http://localhost/crossmark-policy',
    '10.5/x',
    '11.1234/x',
    '10.123/short-prefix',
    '10.1234/',  'not-a-doi',
]);

test('policyDoi returns null for empty input', function () {
    expect(CrossrefConfig::policyDoi(null))->toBeNull();
    expect(CrossrefConfig::policyDoi(''))->toBeNull();
    expect(CrossrefConfig::policyDoi('   '))->toBeNull();
});

test('policyDoi falls back to the deprecated policy URL variable', function () {
    expect(CrossrefConfig::policyDoi(null, '10.5555/legacy-policy'))->toBe('10.5555/legacy-policy');
});

test('policyDoi ignores the legacy variable when its value is not a DOI', function () {
    expect(CrossrefConfig::policyDoi('10.9999/primary', 'https://journal.example/crossmark-policy'))
        ->toBe('10.9999/primary');
    expect(CrossrefConfig::policyDoi(null, 'https://journal.example/crossmark-policy'))->toBeNull();
});

test('misconfigured flags non-empty values that are not a DOI', function (string $value) {
    expect(CrossrefConfig::misconfigured($value))->toBeTrue();
})->with([
    'https://journal.example/crossmark-policy',
    'http://localhost/crossmark-policy',
    '10.5/x',
    'not-a-doi',
]);

test('misconfigured flags the deprecated legacy variable when its value is not a DOI', function () {
    expect(CrossrefConfig::misconfigured(null, 'https://journal.example/crossmark-policy'))->toBeTrue();
});

test('misconfigured is false for valid, empty, and unset input', function () {
    expect(CrossrefConfig::misconfigured('10.5555/crossmark-policy'))->toBeFalse();
    expect(CrossrefConfig::misconfigured(null))->toBeFalse();
    expect(CrossrefConfig::misconfigured(''))->toBeFalse();
    expect(CrossrefConfig::misconfigured('   '))->toBeFalse();
    expect(CrossrefConfig::misconfigured('10.9999/primary', 'https://journal.example/crossmark-policy'))->toBeFalse();
});

test('domains keeps valid dotted hostnames and trims whitespace', function () {
    expect(CrossrefConfig::domains(' journal.example , sub.journal.example '))
        ->toBe(['journal.example', 'sub.journal.example']);
});

test('domains drops dot-less, short, ported, and empty entries', function () {
    expect(CrossrefConfig::domains('localhost, a.b, ab.cd, , journal.example:8080, journal.example'))
        ->toBe(['ab.cd', 'journal.example']);
});

test('domains returns an empty array for empty input', function () {
    expect(CrossrefConfig::domains(''))->toBe([]);
    expect(CrossrefConfig::domains(null))->toBe([]);
    expect(CrossrefConfig::domains('localhost'))->toBe([]);
});
