<?php

namespace App\Models;

class OrderStatus extends LookupRecord
{
    protected $table = 'order_statuses';

    public static function idForLabel(string $label): ?int
    {
        $normalized = $label === 'отменён' ? 'отменен' : $label;

        return static::idFor($normalized);
    }
}
