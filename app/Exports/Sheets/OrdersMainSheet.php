<?php

namespace App\Exports\Sheets;

use App\Exports\Concerns\AppliesReportSheetLayout;
use App\Exports\Concerns\FiltersOrdersForExport;
use App\Exports\Concerns\FormatsOrderExportRows;
use App\Exports\ExportReportMeta;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class OrdersMainSheet implements FromCollection, WithHeadings, WithMapping, WithEvents, WithTitle, WithCustomStartCell
{
    use AppliesReportSheetLayout;
    use FiltersOrdersForExport;
    use FormatsOrderExportRows;

    protected float $sumTotal = 0;

    public function __construct(ExportReportMeta $meta, ?string $status = null, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $this->reportMeta = $meta;
        $this->status = $status;
        $this->dateFrom = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : null;
        $this->dateTo = $dateTo ? Carbon::parse($dateTo)->endOfDay() : null;
    }

    public function title(): string
    {
        return $this->status ? 'Заказы: '.$this->status : 'Заказы';
    }

    public function collection(): Collection
    {
        $query = $this->baseOrderQuery();

        if (! $this->status) {
            $query->excludingCancelled();
        }

        $rows = $query->get();

        $this->sumTotal = (float) $rows->sum(fn ($order) => (float) $order->total_amount);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'ID заказа',
            'Клиент',
            'Email',
            'Сумма',
            'Товары в заказе',
            'Промокод',
            'Скидка',
            'Статус',
            'Способ оплаты',
            'Статус оплаты',
            'Адрес доставки',
            'Создан',
        ];
    }

    public function map($order): array
    {
        return [
            $order->id,
            $order->user?->name,
            $order->user?->email,
            $order->total_amount,
            $this->formatOrderItems($order),
            $order->promo_code ?? '—',
            $order->promo_code ? (float) ($order->discount_amount ?? 0) : 0,
            $order->status,
            $order->payment_method ?? '—',
            $order->payment_status ?? '—',
            $order->delivery_address,
            $this->formatOrderCreatedAt($order),
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->applyReportMetaAndStyles($event, count($this->headings()));

                $columnCount = count($this->headings());
                $event->sheet->append([
                    '',
                    '',
                    $this->status ? 'Итого' : 'Итого (без отменённых)',
                    round($this->sumTotal, 2),
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]);
                $this->highlightTotalRow($event, $columnCount);
            },
        ];
    }
}
