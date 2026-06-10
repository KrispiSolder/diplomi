<?php

namespace App\Exports\Concerns;

use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

trait FiltersOrdersForExport
{
    protected ?string $status;

    protected ?Carbon $dateFrom;

    protected ?Carbon $dateTo;

    protected function baseOrderQuery(): Builder
    {
        $query = Order::query()->with([
            'user',
            'items.product',
            'orderStatusRef',
            'paymentMethodRef',
            'paymentStatusRef',
        ]);

        if ($this->status) {
            $query->whereHas('orderStatusRef', fn ($q) => $q->where('label', $this->status));
        }

        if ($this->dateFrom) {
            $query->where('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('created_at', '<=', $this->dateTo);
        }

        return $query->orderBy('id');
    }
}
