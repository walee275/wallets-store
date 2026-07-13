<?php

use App\Support\Money;

it('formats email currency as PKR 15,150 style', function () {
    expect(Money::formatEmail(1515000, 'PKR'))->toBe('PKR 15,150');
    expect(Money::formatEmail(890000, 'pkr'))->toBe('PKR 8,900');
    expect(Money::formatEmail(19950, 'PKR'))->toBe('PKR 199.50');
});
