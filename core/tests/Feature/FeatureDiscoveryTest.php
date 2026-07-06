<?php

use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature;
use Sqids\Sqids;

it('resolves the shuffled id-alphabet from the discovered feature class', function () {
    $alphabet = Feature::value('id-alphabet');

    // Discovery wires the class to its name, so resolve() runs and returns a
    // permutation of the default alphabet rather than the unknown-feature false.
    expect(mb_strlen($alphabet))->toBe(mb_strlen(Sqids::DEFAULT_ALPHABET))
        ->and(str_split($alphabet))->toEqualCanonicalizing(str_split(Sqids::DEFAULT_ALPHABET));

    // The resolved value is persisted under the concrete "global" scope.
    expect(
        DB::table('features')->where('name', 'id-alphabet')->where('scope', 'global')->exists()
    )->toBeTrue();
});

it('builds a Sqids instance that encodes with the discovered alphabet', function () {
    $code = app(Sqids::class)->encode([1, 2]);

    expect($code)->toBeString()->and($code)->not->toBeEmpty();
});
