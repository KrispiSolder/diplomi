<?php

namespace Tests\Unit;

use App\Support\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_percent_discount_matches_subtotal_minus_total(): void
    {
        $subtotalK = Money::toKopecks(2390.00);
        $discountK = Money::percentOf($subtotalK, 10.0);
        $totalK = $subtotalK - $discountK;

        $this->assertSame(239000, $subtotalK);
        $this->assertSame(23900, $discountK);
        $this->assertSame(215100, $totalK);
        $this->assertSame(239.0, Money::fromKopecks($discountK));
        $this->assertSame(2151.0, Money::fromKopecks($totalK));
    }

    public function test_line_totals_sum_without_float_drift(): void
    {
        $kopecks = Money::lineTotalKopecks(1800, 1) + Money::lineTotalKopecks(590, 1);
        $discountK = Money::percentOf($kopecks, 15);

        $this->assertSame(239000, $kopecks);
        $this->assertSame(35850, $discountK);
        $this->assertSame(358.5, Money::fromKopecks($discountK));
    }
}
