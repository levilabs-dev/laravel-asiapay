<?php

declare(strict_types=1);

use LeviLabs\LaravelAsiapay\Support\TokenNormalizer;

it('strips a Bearer prefix from access tokens', function () {
    expect(TokenNormalizer::stripBearerPrefix('Bearer abc.def.ghi'))->toBe('abc.def.ghi')
        ->and(TokenNormalizer::stripBearerPrefix('abc.def.ghi'))->toBe('abc.def.ghi');
});
