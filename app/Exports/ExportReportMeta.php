<?php

namespace App\Exports;

use Carbon\Carbon;

class ExportReportMeta
{
    public const REPORT_TIMEZONE = 'Asia/Irkutsk';

    public function __construct(
        public string $title,
        public ?string $periodFrom,
        public ?string $periodTo,
        public string $authorName,
        public Carbon $generatedAt,
    ) {}

    public function periodLabel(): string
    {
        if (! $this->periodFrom && ! $this->periodTo) {
            return 'за всё время (без ограничения по датам)';
        }

        $from = $this->periodFrom
            ? Carbon::parse($this->periodFrom, self::REPORT_TIMEZONE)->format('d.m.Y')
            : null;
        $to = $this->periodTo
            ? Carbon::parse($this->periodTo, self::REPORT_TIMEZONE)->format('d.m.Y')
            : null;

        if ($from && $to) {
            return "с {$from} по {$to}";
        }

        if ($from) {
            return "с {$from}";
        }

        return "по {$to}";
    }

    public function generatedAtLabel(): string
    {
        return $this->generatedAt
            ->copy()
            ->timezone(self::REPORT_TIMEZONE)
            ->format('d.m.Y H:i');
    }

    /**
     * @return array<int, array{0: string, 1: string}>
     */
    public function rows(): array
    {
        return [
            ['Вид отчёта', $this->title],
            ['Период отчёта', $this->periodLabel()],
            ['Сформировал', $this->authorName],
            ['Дата и время формирования', $this->generatedAtLabel()],
        ];
    }
}
