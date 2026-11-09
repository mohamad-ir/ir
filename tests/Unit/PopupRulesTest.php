<?php

use App\Models\Popup;

/** Pure rules of the popup module (no database). Also keeps tests/Unit in the repository for CI. */
it('matches page patterns with wildcards and pasted URLs', function () {
    expect(Popup::pathMatches('/courses/*', '/courses/oct-basics'))->toBeTrue()
        ->and(Popup::pathMatches('/courses/*', '/courses'))->toBeFalse()
        ->and(Popup::pathMatches('/', '/'))->toBeTrue()
        ->and(Popup::pathMatches('/*', '/anything/at/all'))->toBeTrue()
        ->and(Popup::pathMatches('https://example.com/cases', '/cases'))->toBeTrue()
        ->and(Popup::pathMatches('', '/cases'))->toBeFalse()
        ->and(Popup::pathMatches('/CASES', '/cases'))->toBeTrue();
});

it('picks readable text colours for custom backgrounds', function () {
    expect(Popup::contrast('#ffffff'))->toBe('#111111')
        ->and(Popup::contrast('#000000'))->toBe('#ffffff')
        ->and(Popup::contrast('#12324f'))->toBe('#ffffff')
        ->and(Popup::contrast('#f5c400'))->toBe('#111111');
});
