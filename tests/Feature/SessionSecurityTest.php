<?php

test('session defaults protect the cookie', function () {
    expect(config('session.encrypt'))->toBeTrue()
        ->and(config('session.http_only'))->toBeTrue()
        ->and(config('session.same_site'))->toBe('lax')
        ->and(config('session.cookie'))->toBeString()->not->toBeEmpty();
});
