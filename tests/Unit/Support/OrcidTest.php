<?php

use App\Support\Orcid;

test('bare ID gets prefixed with orcid.org URL', function () {
    expect(Orcid::url('0000-0001-2345-6789'))->toBe('https://orcid.org/0000-0001-2345-6789');
});

test('already-prefixed URL is returned unchanged', function () {
    expect(Orcid::url('https://orcid.org/0000-0001-2345-6789'))
        ->toBe('https://orcid.org/0000-0001-2345-6789');
    expect(Orcid::url('http://orcid.org/0000-0001-2345-6789'))
        ->toBe('http://orcid.org/0000-0001-2345-6789');
});

test('null or empty input returns null', function () {
    expect(Orcid::url(null))->toBeNull();
    expect(Orcid::url(''))->toBeNull();
    expect(Orcid::url('   '))->toBeNull();
});

test('valid ORCID passes validation', function () {
    expect(Orcid::isValid('0000-0001-2345-6789'))->toBeTrue();
    expect(Orcid::isValid('0000-0001-2345-679X'))->toBeTrue();
});

test('invalid ORCID fails validation', function () {
    expect(Orcid::isValid('0000-0001-2345-678'))->toBeFalse();
    expect(Orcid::isValid('0000-0001-2345-67890'))->toBeFalse();
    expect(Orcid::isValid('0000-0001-2345-678Z'))->toBeFalse();
    expect(Orcid::isValid('not-an-orcid'))->toBeFalse();
    expect(Orcid::isValid('0000000123456789'))->toBeFalse();
});

test('null or empty ORCID is considered valid', function () {
    expect(Orcid::isValid(null))->toBeTrue();
    expect(Orcid::isValid(''))->toBeTrue();
});
