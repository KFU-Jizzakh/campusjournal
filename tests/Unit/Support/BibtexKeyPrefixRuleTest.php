<?php

use App\Rules\BibtexKeyPrefix;

test('valid prefix passes validation', function (?string $prefix) {
    $failed = false;
    (new BibtexKeyPrefix)->validate('prefix', $prefix, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
})->with(['gcru', 'j-e2', '_x', 'A1_b-9', '', null]);

test('invalid prefix fails validation', function (string $prefix) {
    $failed = false;
    (new BibtexKeyPrefix)->validate('prefix', $prefix, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeTrue();
})->with(['my journal', 'pre{fix', 'префикс', 'a,b', 'key!']);
