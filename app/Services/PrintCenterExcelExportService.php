<?php

namespace App\Services;

use App\Models\Event;
use App\Models\LiveResultCategory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PrintCenterExcelExportService
{
    /**
     * @param  array<int, array{category: LiveResultCategory, round: string, sheetData: array}>  $categoriesData
     */
    public function build(Event $event, array $categoriesData): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->removeSheetByIndex(0);

        $usedTitles = [];

        foreach ($categoriesData as $item) {
            /** @var LiveResultCategory $category */
            $category = $item['category'];
            $round = $item['round'];
            $sheetData = $item['sheetData'];

            $title = $this->uniqueSheetTitle((string) $category->title, $usedTitles);
            $sheet = new Worksheet($spreadsheet, $title);
            $spreadsheet->addSheet($sheet);

            $this->fillCategorySheet($sheet, $event, (string) $category->title, $round, $sheetData);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  array<string, mixed>  $sheetData
     */
    protected function fillCategorySheet(Worksheet $sheet, Event $event, string $categoryTitle, string $round, array $sheetData): void
    {
        $isQual = (bool) ($sheetData['is_qualifikasi'] ?? false);
        $headers = ['Plate', 'Riders'];
        if ($isQual) {
            $headers[] = 'Total';
        }
        $headers[] = 'Rank';
        $headers[] = 'Ket';

        $colCount = count($headers);
        $lastCol = Coordinate::stringFromColumnIndex($colCount);

        $row = 1;
        $sheet->setCellValue([1, $row], $event->title);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle([1, $row])->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle([1, $row])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row++;
        $sheet->setCellValue([1, $row], $categoryTitle.' - '.$round);
        $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
        $sheet->getStyle([1, $row])->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle([1, $row])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        if (! empty($sheetData['keterangan'])) {
            $row++;
            $sheet->setCellValue([1, $row], __('Keterangan').': '.$sheetData['keterangan']);
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->getStyle([1, $row])->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        }

        $row += 2;

        foreach ($sheetData['groups'] ?? [] as $group) {
            $sheet->setCellValue([1, $row], $group['name'] ?? '');
            $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
            $sheet->getStyle([1, $row])->getFont()->setBold(true);
            $row++;

            $headerRow = $row;
            for ($c = 1; $c <= $colCount; $c++) {
                $sheet->setCellValue([$c, $row], $headers[$c - 1]);
            }
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFont()->setBold(true);
            $sheet->getStyle("A{$headerRow}:{$lastCol}{$headerRow}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');
            $row++;

            $dataRows = $group['data'] ?? [];
            if (empty($dataRows)) {
                $sheet->setCellValue([1, $row], __('Tidak ada data'));
                $sheet->mergeCells("A{$row}:{$lastCol}{$row}");
                $sheet->getStyle([1, $row])->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row += 2;

                continue;
            }

            foreach ($dataRows as $dataRow) {
                $col = 1;
                $sheet->setCellValue([$col++, $row], $dataRow['plate'] ?? '');
                $sheet->setCellValue([$col++, $row], $this->formatRidersCell($dataRow));
                if ($isQual) {
                    $sheet->setCellValue([$col++, $row], $dataRow['total'] ?? '');
                }
                $sheet->setCellValue([$col++, $row], $dataRow['rank'] ?? '');
                $sheet->setCellValue([$col++, $row], $dataRow['ket'] ?? '');

                $sheet->getStyle('B'.$row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                $row++;
            }

            $row++;
        }

        foreach (range(1, $colCount) as $c) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function formatRidersCell(array $row): string
    {
        $parts = array_filter([
            trim((string) ($row['nama'] ?? '')),
            trim((string) ($row['panggilan'] ?? '')),
            trim((string) ($row['team'] ?? '')),
        ], fn (string $v) => $v !== '');

        return implode("\n", $parts);
    }

    /**
     * @param  array<string, true>  $used
     */
    protected function uniqueSheetTitle(string $title, array &$used): string
    {
        $invalid = ['\\', '/', '?', '*', '[', ']', ':'];
        $base = str_replace($invalid, '-', $title);
        $base = trim($base) !== '' ? $base : 'Kategori';
        $base = mb_substr($base, 0, 31);

        if (! isset($used[$base])) {
            $used[$base] = true;

            return $base;
        }

        for ($i = 2; $i < 1000; $i++) {
            $suffix = ' ('.$i.')';
            $trunc = mb_substr($base, 0, max(1, 31 - mb_strlen($suffix))).$suffix;
            if (! isset($used[$trunc])) {
                $used[$trunc] = true;

                return $trunc;
            }
        }

        $fallback = 'Sheet'.count($used);
        $used[$fallback] = true;

        return mb_substr($fallback, 0, 31);
    }
}
