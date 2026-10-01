<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

class Ean13Barcode
{
    private const LEFT_PARITY = ['LLLLLL', 'LLGLGG', 'LLGGLG', 'LLGGGL', 'LGLLGG', 'LGGLLG', 'LGGGLL', 'LGLGLG', 'LGLGGL', 'LGGLGL'];

    private const PATTERNS = [
        'L' => ['0001101', '0011001', '0010011', '0111101', '0100011', '0110001', '0101111', '0111011', '0110111', '0001011'],
        'G' => ['0100111', '0110011', '0011011', '0100001', '0011101', '0111001', '0000101', '0010001', '0001001', '0010111'],
        'R' => ['1110010', '1100110', '1101100', '1000010', '1011100', '1001110', '1010000', '1000100', '1001000', '1110100'],
    ];

    public function __construct(private readonly string $code)
    {
        throw_unless(preg_match('/^\d{13}$/', $code), InvalidArgumentException::class, 'An EAN-13 barcode needs exactly 13 digits.');
    }

    /**
     * The 95 black and white modules that make up the barcode, as a string of 1s and 0s.
     */
    public function modules(): string
    {
        $digits = array_map(intval(...), str_split($this->code));
        $parity = self::LEFT_PARITY[$digits[0]];

        $left = collect(range(1, 6))->map(fn (int $position): string => self::PATTERNS[$parity[$position - 1]][$digits[$position]])->implode('');
        $right = collect(range(7, 12))->map(fn (int $position): string => self::PATTERNS['R'][$digits[$position]])->implode('');

        return '101'.$left.'01010'.$right.'101';
    }

    public function toSvg(int $moduleWidth = 2, int $height = 60): string
    {
        $bars = collect(str_split($this->modules()))
            ->map(fn (string $module, int $index): string => $module === '1'
                ? sprintf('<rect x="%d" y="0" width="%d" height="%d"/>', $index * $moduleWidth, $moduleWidth, $height)
                : '')
            ->implode('');

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %2$d" width="%1$d" height="%2$d" role="img" aria-label="Barcode %3$s">%4$s</svg>',
            95 * $moduleWidth,
            $height,
            $this->code,
            $bars,
        );
    }
}
