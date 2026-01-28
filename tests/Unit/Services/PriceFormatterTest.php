<?php

declare(strict_types=1);

use App\Services\PriceFormatter;
use App\ValueObjects\Price;

beforeEach(function (): void {
    $this->formatter = new PriceFormatter();
});

describe('format', function (): void {
    it('formats USD amount with symbol', function (): void {
        $result = $this->formatter->format(100.0, 'USD');

        expect($result)->toBe('$100');
    });

    it('formats USD amount with cents', function (): void {
        $result = $this->formatter->format(99.50, 'USD');

        expect($result)->toBe('$99.50');
    });

    it('formats RWF amount without decimals', function (): void {
        $result = $this->formatter->format(135000.0, 'RWF');

        expect($result)->toBe('FRW135,000');
    });

    it('formats RWF amount and rounds fractional values', function (): void {
        $result = $this->formatter->format(135000.75, 'RWF');

        expect($result)->toBe('FRW135,001');
    });

    it('normalizes FRW to RWF', function (): void {
        $result = $this->formatter->format(1000.0, 'FRW');

        expect($result)->toBe('FRW1,000');
    });

    it('handles lowercase currency codes', function (): void {
        $result = $this->formatter->format(100.0, 'usd');

        expect($result)->toBe('$100');
    });

    it('formats zero amount correctly', function (): void {
        $result = $this->formatter->format(0.0, 'USD');

        expect($result)->toBe('$0');
    });

    it('formats small amounts with decimals', function (): void {
        $result = $this->formatter->format(0.99, 'USD');

        expect($result)->toBe('$0.99');
    });

    it('hides decimals for whole USD amounts', function (): void {
        $result = $this->formatter->format(100.00, 'USD');

        expect($result)->toBe('$100');
    });
});

describe('formatPrice', function (): void {
    it('formats Price value object', function (): void {
        $price = Price::fromMajorUnits(100.50, 'USD');
        $result = $this->formatter->formatPrice($price);

        expect($result)->toBe('$100.50');
    });

    it('formats Price value object with RWF', function (): void {
        $price = Price::fromMajorUnits(135000.0, 'RWF');
        $result = $this->formatter->formatPrice($price);

        expect($result)->toBe('FRW135,000');
    });
});

describe('formatFromMinorUnits', function (): void {
    it('converts cents to dollars and formats', function (): void {
        $result = $this->formatter->formatFromMinorUnits(10050, 'USD');

        expect($result)->toBe('$100.50');
    });

    it('converts minor units for RWF and formats', function (): void {
        $result = $this->formatter->formatFromMinorUnits(13500000, 'RWF');

        expect($result)->toBe('FRW135,000');
    });
});

describe('getSymbol', function (): void {
    it('returns correct symbol for USD', function (): void {
        $result = $this->formatter->getSymbol('USD');

        expect($result)->toBe('$');
    });

    it('returns correct symbol for RWF', function (): void {
        $result = $this->formatter->getSymbol('RWF');

        expect($result)->toBe('FRW');
    });

    it('returns correct symbol for EUR', function (): void {
        $result = $this->formatter->getSymbol('EUR');

        expect($result)->toBe('€');
    });

    it('normalizes FRW to RWF symbol', function (): void {
        $result = $this->formatter->getSymbol('FRW');

        expect($result)->toBe('FRW');
    });
});

describe('getDecimals', function (): void {
    it('returns 2 decimals for USD with fractional amount', function (): void {
        $result = $this->formatter->getDecimals('USD', 99.50);

        expect($result)->toBe(2);
    });

    it('returns 0 decimals for USD with whole amount', function (): void {
        $result = $this->formatter->getDecimals('USD', 100.0);

        expect($result)->toBe(0);
    });

    it('returns 0 decimals for RWF regardless of amount', function (): void {
        $result = $this->formatter->getDecimals('RWF', 135000.75);

        expect($result)->toBe(0);
    });

    it('returns 0 decimals for JPY', function (): void {
        $result = $this->formatter->getDecimals('JPY', 1000.50);

        expect($result)->toBe(0);
    });
});

describe('currencyHasDecimals', function (): void {
    it('returns true for USD', function (): void {
        $result = $this->formatter->currencyHasDecimals('USD');

        expect($result)->toBeTrue();
    });

    it('returns false for RWF', function (): void {
        $result = $this->formatter->currencyHasDecimals('RWF');

        expect($result)->toBeFalse();
    });

    it('returns false for JPY', function (): void {
        $result = $this->formatter->currencyHasDecimals('JPY');

        expect($result)->toBeFalse();
    });
});

describe('normalizeCurrency', function (): void {
    it('normalizes FRW to RWF', function (): void {
        $result = $this->formatter->normalizeCurrency('FRW');

        expect($result)->toBe('RWF');
    });

    it('normalizes lowercase to uppercase', function (): void {
        $result = $this->formatter->normalizeCurrency('usd');

        expect($result)->toBe('USD');
    });

    it('keeps valid currency codes unchanged', function (): void {
        $result = $this->formatter->normalizeCurrency('USD');

        expect($result)->toBe('USD');
    });
});

describe('minorToMajorUnits', function (): void {
    it('converts cents to dollars', function (): void {
        $result = $this->formatter->minorToMajorUnits(10050);

        expect($result)->toBe(100.50);
    });

    it('handles zero', function (): void {
        $result = $this->formatter->minorToMajorUnits(0);

        expect($result)->toBe(0.0);
    });

    it('handles large amounts', function (): void {
        $result = $this->formatter->minorToMajorUnits(13500000);

        expect($result)->toBe(135000.0);
    });
});

describe('convertAndFormatFromMinorUnits', function (): void {
    it('formats same currency without conversion', function (): void {
        $result = $this->formatter->convertAndFormatFromMinorUnits(10050, 'USD', 'USD');

        expect($result)->toBe('$100.50');
    });

    it('uses converter when currencies differ', function (): void {
        // 10000 cents = $100, $100 * 1350 = 135,000 RWF
        $converter = fn (float $amount, string $from, string $to): float => $amount * 1350;

        $result = $this->formatter->convertAndFormatFromMinorUnits(10000, 'USD', 'RWF', $converter);

        expect($result)->toBe('FRW135,000');
    });

    it('falls back to source currency on converter failure', function (): void {
        $converter = function (): never {
            throw new Exception('Conversion failed');
        };

        $result = $this->formatter->convertAndFormatFromMinorUnits(10000, 'USD', 'RWF', $converter);

        expect($result)->toBe('$100');
    });

    it('formats in source currency when no converter provided', function (): void {
        $result = $this->formatter->convertAndFormatFromMinorUnits(10000, 'USD', 'RWF');

        expect($result)->toBe('$100');
    });
});

describe('consistency', function (): void {
    it('formats the same amount consistently across multiple calls', function (): void {
        $amount = 203755.41;

        $result1 = $this->formatter->format($amount, 'RWF');
        $result2 = $this->formatter->format($amount, 'RWF');
        $result3 = $this->formatter->format($amount, 'RWF');

        expect($result1)->toBe($result2);
        expect($result2)->toBe($result3);
        expect($result1)->toBe('FRW203,755');
    });

    it('formats USD consistently with and without decimals', function (): void {
        expect($this->formatter->format(100.0, 'USD'))->toBe('$100');
        expect($this->formatter->format(100.00, 'USD'))->toBe('$100');
        expect($this->formatter->format(100.50, 'USD'))->toBe('$100.50');
        expect($this->formatter->format(100.005, 'USD'))->toBe('$100'); // Rounds to $100.01 which rounds to 0 decimals
    });
});
