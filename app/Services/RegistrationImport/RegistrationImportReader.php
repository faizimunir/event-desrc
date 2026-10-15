<?php

namespace App\Services\RegistrationImport;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Throwable;

/**
 * Membaca file upload (.xlsx / .csv) menjadi baris mentah (semua nilai string) berdasarkan nama header.
 */
class RegistrationImportReader
{
    public const MAX_ROWS = 500;

    /** Batas pemindaian baris (termasuk baris kosong) agar file sampah tidak membuat proses lama. */
    private const MAX_SCANNED_ROWS = 20000;

    /**
     * @return list<array{row: int, data: array<string, ?string>}>
     *
     * @throws RegistrationImportException
     */
    public function read(string $path, string $extension, int $eventId): array
    {
        $extension = strtolower($extension);
        if (! in_array($extension, ['xlsx', 'csv'], true)) {
            throw new RegistrationImportException(__('Only .xlsx and .csv files are supported.'));
        }

        if ($extension === 'xlsx' && ! RegistrationImportTemplate::excelSupported()) {
            throw new RegistrationImportException(__('This server cannot read Excel files (the PHP "zip" extension is not enabled). Save the sheet as .csv and upload that instead.'));
        }

        try {
            $reader = IOFactory::createReader($extension === 'csv' ? 'Csv' : 'Xlsx');
            $reader->setReadEmptyCells(false);
            if ($reader instanceof Csv) {
                $reader->setInputEncoding(Csv::GUESS_ENCODING);
            }
            $spreadsheet = $reader->load($path);
        } catch (Throwable) {
            throw new RegistrationImportException(__('The file could not be read. Please use the downloaded template.'));
        }

        $properties = $spreadsheet->getProperties();
        if ($properties->isCustomPropertySet(RegistrationImportTemplate::PROPERTY_EVENT_ID)
            && (string) $properties->getCustomPropertyValue(RegistrationImportTemplate::PROPERTY_EVENT_ID) !== (string) $eventId) {
            throw new RegistrationImportException(__('This template was downloaded for a different event. Download the template from this event and try again.'));
        }

        $sheet = $spreadsheet->getSheetByName(RegistrationImportTemplate::SHEET_DATA) ?? $spreadsheet->getSheet(0);

        $columns = [];
        $highestColumn = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
        for ($c = 1; $c <= $highestColumn; $c++) {
            $header = $this->normalizeHeader((string) $this->cellString($sheet->getCell([$c, 1]), ''));
            if (array_key_exists($header, RegistrationImportTemplate::COLUMNS) && ! isset($columns[$header])) {
                $columns[$header] = $c;
            }
        }

        $missing = array_values(array_filter(
            array_keys(array_filter(RegistrationImportTemplate::COLUMNS)),
            fn (string $name) => ! isset($columns[$name])
        ));
        if ($missing !== []) {
            throw new RegistrationImportException(__('Missing required column(s): :columns. Please use the downloaded template.', ['columns' => implode(', ', $missing)]));
        }

        $highestRow = $sheet->getHighestDataRow();
        if ($highestRow > self::MAX_SCANNED_ROWS) {
            throw new RegistrationImportException(__('The file has too many rows. The maximum is :max rows per file.', ['max' => self::MAX_ROWS]));
        }

        $rows = [];
        for ($r = 2; $r <= $highestRow; $r++) {
            $data = [];
            foreach (array_keys(RegistrationImportTemplate::COLUMNS) as $name) {
                $data[$name] = isset($columns[$name])
                    ? $this->cellString($sheet->getCell([$columns[$name], $r]), $name)
                    : null;
            }
            if (array_filter($data, fn (?string $v) => $v !== null) === []) {
                continue;
            }
            $rows[] = ['row' => $r, 'data' => $data];
            if (count($rows) > self::MAX_ROWS) {
                throw new RegistrationImportException(__('The file has too many rows. The maximum is :max rows per file.', ['max' => self::MAX_ROWS]));
            }
        }

        $spreadsheet->disconnectWorksheets();

        if ($rows === []) {
            throw new RegistrationImportException(__('The file contains no registration rows.'));
        }

        return $rows;
    }

    protected function normalizeHeader(string $header): string
    {
        $header = mb_strtolower(trim(str_replace('*', '', $header)));

        return trim((string) preg_replace('/[\s\-]+/u', '_', $header), '_');
    }

    protected function cellString(Cell $cell, string $column): ?string
    {
        try {
            $value = $cell->getCalculatedValue();
        } catch (Throwable) {
            $value = $cell->getValue();
        }

        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            if ($column === 'dob') {
                try {
                    return Date::excelToDateTimeObject($value)->format('Y-m-d');
                } catch (Throwable) {
                    return (string) $value;
                }
            }

            // Hindari notasi ilmiah (mis. nomor WA yang tersimpan sebagai angka).
            return is_float($value) && floor($value) !== $value
                ? rtrim(rtrim(sprintf('%.10F', $value), '0'), '.')
                : sprintf('%.0F', $value);
        }

        $value = trim((string) preg_replace('/\s+/u', ' ', (string) $value));

        return $value === '' ? null : $value;
    }
}
