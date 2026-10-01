<?php

declare(strict_types=1);

use App\Support\Ean13Barcode;

it('starts, splits and ends with the standard guard bars', function (): void {
    $modules = (new Ean13Barcode('4291234567891'))->modules();

    expect(strlen($modules))->toBe(95)
        ->and(substr($modules, 0, 3))->toBe('101')
        ->and(substr($modules, 45, 5))->toBe('01010')
        ->and(substr($modules, -3))->toBe('101');
});

it('uses the first digit to pick the parity of the left half', function (): void {
    $modules = (new Ean13Barcode('5901234123457'))->modules();

    expect(substr($modules, 3, 7))->toBe('0001011')
        ->and(substr($modules, 10, 7))->toBe('0100111');
});

it('rejects codes that are not 13 digits', function (): void {
    new Ean13Barcode('12345');
})->throws(InvalidArgumentException::class);
