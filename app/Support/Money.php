<?php

namespace App\Support;

final class Money
{
    public static function toKopecks(float|string|null $rubles): int
    {
        return (int) round((float) $rubles * 100, 0, PHP_ROUND_HALF_UP);
    }

    public static function fromKopecks(int $kopecks): float
    {
        return round($kopecks / 100, 2);
    }

    public static function roundRubles(float|string|null $rubles): float
    {
        return self::fromKopecks(self::toKopecks($rubles));
    }

    public static function percentOf(int $baseKopecks, float $percent): int
    {
        return (int) round($baseKopecks * (float) $percent / 100, 0, PHP_ROUND_HALF_UP);
    }

    public static function lineTotalKopecks(float|string|null $unitPrice, int $quantity): int
    {
        return self::toKopecks($unitPrice) * max(0, $quantity);
    }
}
