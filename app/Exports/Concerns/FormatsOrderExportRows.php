<?php

namespace App\Exports\Concerns;

use App\Models\Order;

trait FormatsOrderExportRows
{
    public const REPORT_TIMEZONE = 'Asia/Irkutsk';

    protected function formatOrderItems(Order $order): string
    {
        $order->loadMissing('items');

        if ($order->items->isEmpty()) {
            return '—';
        }

        return $order->items
            ->map(function ($item) {
                $name = $item->product_name
                    ?? $item->product?->name
                    ?? 'Товар';

                return $name.' ×'.(int) $item->quantity;
            })
            ->implode('; ');
    }

    protected function formatOrderCreatedAt(Order $order): string
    {
        return $order->created_at
            ? $order->created_at->timezone(self::REPORT_TIMEZONE)->format('d.m.Y H:i')
            : '—';
    }
}
