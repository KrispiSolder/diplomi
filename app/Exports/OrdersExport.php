<?php

namespace App\Exports;

use App\Exports\Sheets\OrdersCancelledSheet;
use App\Exports\Sheets\OrdersMainSheet;
use App\Models\Order;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class OrdersExport implements WithMultipleSheets
{
    protected ?string $status;

    protected ?Carbon $dateFrom;

    protected ?Carbon $dateTo;

    protected ExportReportMeta $meta;

    public function __construct(ExportReportMeta $meta, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->meta = $meta;
        $this->status = $status;
        $this->dateFrom = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $this->dateTo = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;
    }

    public function sheets(): array
    {
        $dateFrom = $this->dateFrom?->toDateString();
        $dateTo = $this->dateTo?->toDateString();

        if ($this->status === 'отменен') {
            return [new OrdersCancelledSheet($this->meta, $this->status, $dateFrom, $dateTo)];
        }

        if ($this->status) {
            return [new OrdersMainSheet($this->meta, $this->status, $dateFrom, $dateTo)];
        }

        return [
            new OrdersMainSheet($this->meta, null, $dateFrom, $dateTo),
            new OrdersCancelledSheet($this->meta, null, $dateFrom, $dateTo),
        ];
    }

    public function isEmpty(): bool
    {
        $query = Order::query();

        if ($this->status) {
            $query->whereHas('orderStatusRef', fn ($q) => $q->where('label', $this->status));
        }

        if ($this->dateFrom) {
            $query->where('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->where('created_at', '<=', $this->dateTo);
        }

        return $query->count() === 0;
    }
}
