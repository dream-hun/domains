<?php

declare(strict_types=1);

use App\ValueObjects\Price;

describe('construction', function (): void {
    it('creates Price from minor units', function (): void {
        $price = Price::fromMinorUnits(10050, 'USD');

        expect($price->getAmountInMinorUnits())->toBe(10050);
        expect($price->getCurrency())->toBe('USD');
    });

    it('creates Price from major units', function (): void {
        $price = Price::fromMajorUnits(100.50, 'USD');

        expect($price->getAmountInMinorUnits())->toBe(10050);
        expect($price->toMajorUnits())->toBe(100.50);
    });

    it('creates zero Price', function (): void {
        $price = Price::zero('USD');

        expect($price->getAmountInMinorUnits())->toBe(0);
        expect($price->isZero())->toBeTrue();
    });

    it('throws exception for negative amount in constructor', function (): void {
        Price::fromMinorUnits(-100, 'USD');
    })->throws(InvalidArgumentException::class, 'Price amount cannot be negative');

    it('throws exception for negative amount from major units', function (): void {
        Price::fromMajorUnits(-100.0, 'USD');
    })->throws(InvalidArgumentException::class, 'Price amount cannot be negative');

    it('throws exception for invalid currency code', function (): void {
        Price::fromMajorUnits(100.0, 'INVALID');
    })->throws(InvalidArgumentException::class, 'Invalid currency code');

    it('normalizes FRW to RWF', function (): void {
        $price = Price::fromMajorUnits(1000.0, 'FRW');

        expect($price->getCurrency())->toBe('RWF');
        expect($price->getRawCurrency())->toBe('FRW');
    });
});

describe('toMajorUnits', function (): void {
    it('converts minor units to major units', function (): void {
        $price = Price::fromMinorUnits(10050, 'USD');

        expect($price->toMajorUnits())->toBe(100.50);
    });

    it('handles zero correctly', function (): void {
        $price = Price::fromMinorUnits(0, 'USD');

        expect($price->toMajorUnits())->toBe(0.0);
    });
});

describe('format', function (): void {
    it('formats USD price correctly', function (): void {
        $price = Price::fromMajorUnits(100.50, 'USD');

        expect($price->format())->toBe('$100.50');
    });

    it('formats RWF price correctly', function (): void {
        $price = Price::fromMajorUnits(135000.0, 'RWF');

        expect($price->format())->toBe('FRW135,000');
    });

    it('formats whole USD amounts without decimals', function (): void {
        $price = Price::fromMajorUnits(100.0, 'USD');

        expect($price->format())->toBe('$100');
    });
});

describe('add', function (): void {
    it('adds two prices with same currency', function (): void {
        $price1 = Price::fromMajorUnits(100.0, 'USD');
        $price2 = Price::fromMajorUnits(50.0, 'USD');
        $result = $price1->add($price2);

        expect($result->toMajorUnits())->toBe(150.0);
        expect($result->getCurrency())->toBe('USD');
    });

    it('throws exception when adding different currencies', function (): void {
        $price1 = Price::fromMajorUnits(100.0, 'USD');
        $price2 = Price::fromMajorUnits(50.0, 'RWF');

        $price1->add($price2);
    })->throws(InvalidArgumentException::class, 'Cannot add prices with different currencies');
});

describe('subtract', function (): void {
    it('subtracts two prices with same currency', function (): void {
        $price1 = Price::fromMajorUnits(100.0, 'USD');
        $price2 = Price::fromMajorUnits(30.0, 'USD');
        $result = $price1->subtract($price2);

        expect($result->toMajorUnits())->toBe(70.0);
    });

    it('throws exception when subtracting different currencies', function (): void {
        $price1 = Price::fromMajorUnits(100.0, 'USD');
        $price2 = Price::fromMajorUnits(50.0, 'RWF');

        $price1->subtract($price2);
    })->throws(InvalidArgumentException::class, 'Cannot subtract prices with different currencies');

    it('throws exception when result would be negative', function (): void {
        $price1 = Price::fromMajorUnits(50.0, 'USD');
        $price2 = Price::fromMajorUnits(100.0, 'USD');

        $price1->subtract($price2);
    })->throws(InvalidArgumentException::class, 'Subtraction would result in negative price');
});

describe('multiply', function (): void {
    it('multiplies price by a factor', function (): void {
        $price = Price::fromMajorUnits(100.0, 'USD');
        $result = $price->multiply(2.5);

        expect($result->toMajorUnits())->toBe(250.0);
    });

    it('handles fractional results', function (): void {
        $price = Price::fromMajorUnits(100.0, 'USD');
        $result = $price->multiply(0.1);

        expect($result->toMajorUnits())->toBe(10.0);
    });

    it('throws exception for negative factor', function (): void {
        $price = Price::fromMajorUnits(100.0, 'USD');

        $price->multiply(-2.0);
    })->throws(InvalidArgumentException::class, 'Factor cannot be negative');
});

describe('equals', function (): void {
    it('returns true for equal prices', function (): void {
        $price1 = Price::fromMajorUnits(100.0, 'USD');
        $price2 = Price::fromMajorUnits(100.0, 'USD');

        expect($price1->equals($price2))->toBeTrue();
    });

    it('returns false for different amounts', function (): void {
        $price1 = Price::fromMajorUnits(100.0, 'USD');
        $price2 = Price::fromMajorUnits(200.0, 'USD');

        expect($price1->equals($price2))->toBeFalse();
    });

    it('returns false for different currencies', function (): void {
        $price1 = Price::fromMajorUnits(100.0, 'USD');
        $price2 = Price::fromMajorUnits(100.0, 'RWF');

        expect($price1->equals($price2))->toBeFalse();
    });
});

describe('isGreaterThan', function (): void {
    it('returns true when first price is greater', function (): void {
        $price1 = Price::fromMajorUnits(200.0, 'USD');
        $price2 = Price::fromMajorUnits(100.0, 'USD');

        expect($price1->isGreaterThan($price2))->toBeTrue();
    });

    it('returns false when first price is smaller', function (): void {
        $price1 = Price::fromMajorUnits(50.0, 'USD');
        $price2 = Price::fromMajorUnits(100.0, 'USD');

        expect($price1->isGreaterThan($price2))->toBeFalse();
    });

    it('returns false when prices are equal', function (): void {
        $price1 = Price::fromMajorUnits(100.0, 'USD');
        $price2 = Price::fromMajorUnits(100.0, 'USD');

        expect($price1->isGreaterThan($price2))->toBeFalse();
    });

    it('throws exception for different currencies', function (): void {
        $price1 = Price::fromMajorUnits(100.0, 'USD');
        $price2 = Price::fromMajorUnits(100.0, 'RWF');

        $price1->isGreaterThan($price2);
    })->throws(InvalidArgumentException::class, 'Cannot compare prices with different currencies');
});

describe('isLessThan', function (): void {
    it('returns true when first price is smaller', function (): void {
        $price1 = Price::fromMajorUnits(50.0, 'USD');
        $price2 = Price::fromMajorUnits(100.0, 'USD');

        expect($price1->isLessThan($price2))->toBeTrue();
    });

    it('returns false when first price is greater', function (): void {
        $price1 = Price::fromMajorUnits(200.0, 'USD');
        $price2 = Price::fromMajorUnits(100.0, 'USD');

        expect($price1->isLessThan($price2))->toBeFalse();
    });
});

describe('isZero', function (): void {
    it('returns true for zero price', function (): void {
        $price = Price::zero('USD');

        expect($price->isZero())->toBeTrue();
    });

    it('returns false for non-zero price', function (): void {
        $price = Price::fromMajorUnits(100.0, 'USD');

        expect($price->isZero())->toBeFalse();
    });
});
