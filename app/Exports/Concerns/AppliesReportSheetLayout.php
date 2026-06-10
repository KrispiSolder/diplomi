<?php

namespace App\Exports\Concerns;

use App\Exports\ExportReportMeta;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

trait AppliesReportSheetLayout
{
    public const REPORT_META_ROW_COUNT = 5;

    protected ExportReportMeta $reportMeta;

    public function startCell(): string
    {
        return 'A'.self::REPORT_META_ROW_COUNT;
    }

    protected function headerRowIndex(): int
    {
        return self::REPORT_META_ROW_COUNT;
    }

    protected function applyReportMetaAndStyles(AfterSheet $event, int $columnCount): void
    {
        $sheet = $event->sheet->getDelegate();
        $headerRow = $this->headerRowIndex();
        $lastColumn = Coordinate::stringFromColumnIndex(max(1, $columnCount));

        foreach ($this->reportMeta->rows() as $index => [$label, $value]) {
            $row = $index + 1;
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("B{$row}", $value);
            $sheet->mergeCells("B{$row}:{$lastColumn}{$row}");
        }

        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2E7D32'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);

        for ($col = 1; $col <= $columnCount; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($letter)->setAutoSize(true);
        }

        $itemsColumn = Coordinate::stringFromColumnIndex(5);
        $lastRow = (int) $sheet->getHighestRow();
        if ($lastRow > $headerRow && $columnCount >= 5) {
            $sheet->getStyle("{$itemsColumn}".($headerRow + 1).":{$itemsColumn}{$lastRow}")
                ->getAlignment()
                ->setWrapText(true);
            $sheet->getColumnDimension($itemsColumn)->setWidth(48);
        }

        $sheet->getStyle('A1:A4')->getFont()->setBold(true);
    }

    protected function highlightTotalRow(AfterSheet $event, int $columnCount): void
    {
        $sheet = $event->sheet->getDelegate();
        $totalRow = (int) $sheet->getHighestRow();
        $headerRow = $this->headerRowIndex();

        if ($totalRow <= $headerRow) {
            return;
        }

        $lastColumn = Coordinate::stringFromColumnIndex(max(1, $columnCount));

        $sheet->getStyle("A{$totalRow}:{$lastColumn}{$totalRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1B5E20']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5E9'],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E7D32']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2E7D32']],
            ],
        ]);
    }
}
