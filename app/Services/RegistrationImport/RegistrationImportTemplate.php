<?php

namespace App\Services\RegistrationImport;

use App\Models\Bracket;
use App\Models\Event;
use App\Models\Package;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Document\Properties;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Membuat template Excel bulk registrasi per event. Kolom, dropdown, dan sheet referensi
 * mengikuti bracket/paket/ukuran jersey event tersebut.
 */
class RegistrationImportTemplate
{
    public const SHEET_DATA = 'Registrations';

    public const SHEET_INSTRUCTIONS = 'Instructions';

    public const SHEET_REFERENCE = 'Reference';

    /** Nama properti dokumen yang menyimpan ID event pemilik template. */
    public const PROPERTY_EVENT_ID = 'event_id';

    /** Header kolom (urutan di template) dan apakah wajib diisi. */
    public const COLUMNS = [
        'parent_name' => true,
        'whatsapp' => true,
        'rider_name' => true,
        'nickname' => false,
        'pob' => false,
        'dob' => true,
        'gender' => true,
        'number_plate' => false,
        'team' => true,
        'bracket' => true,
        'package' => true,
        'jersey_size' => false,
    ];

    public const GENDERS = ['boys', 'girls', 'other'];

    /** Membaca/menulis .xlsx membutuhkan ekstensi PHP zip. */
    public static function excelSupported(): bool
    {
        return class_exists(\ZipArchive::class);
    }

    public function build(Event $event): Spreadsheet
    {
        $event->loadMissing('packages.rewards');
        $brackets = $event->brackets()->orderBy('name')->get();
        $packages = $event->packages
            ->where('status', Package::STATUS_ACTIVE)
            ->sortBy('name')
            ->values();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Registration import - '.$event->title)
            ->setCustomProperty(self::PROPERTY_EVENT_ID, (string) $event->id, Properties::PROPERTY_TYPE_STRING);

        $data = $spreadsheet->getActiveSheet();
        $data->setTitle(self::SHEET_DATA);
        $instructions = new Worksheet($spreadsheet, self::SHEET_INSTRUCTIONS);
        $reference = new Worksheet($spreadsheet, self::SHEET_REFERENCE);
        $spreadsheet->addSheet($instructions);
        $spreadsheet->addSheet($reference);

        $ranges = $this->fillReference($reference, $event, $brackets, $packages);
        $this->fillData($data, $ranges);
        $this->fillInstructions($instructions, $event);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Bracket>  $brackets
     * @param  \Illuminate\Support\Collection<int, Package>  $packages
     * @return array{bracket: ?string, package: ?string, jersey: ?string, gender: string}
     */
    protected function fillReference(Worksheet $sheet, Event $event, $brackets, $packages): array
    {
        $sheet->fromArray(['Bracket', 'Bracket rule', 'Package (active)', 'Payable amount (Rp)', 'Needs jersey size', 'Jersey size', 'Gender'], null, 'A1');

        $row = 2;
        foreach ($brackets as $bracket) {
            $sheet->setCellValueExplicit([1, $row], (string) $bracket->name, DataType::TYPE_STRING);
            $sheet->setCellValueExplicit([2, $row], $this->describeBracket($bracket), DataType::TYPE_STRING);
            $row++;
        }
        $lastBracketRow = $row - 1;

        $row = 2;
        foreach ($packages as $package) {
            $sheet->setCellValueExplicit([3, $row], (string) $package->name, DataType::TYPE_STRING);
            $sheet->setCellValue([4, $row], $package->payableAmount());
            $sheet->setCellValue([5, $row], $package->hasJerseyReward() ? 'Yes' : 'No');
            $row++;
        }
        $lastPackageRow = $row - 1;

        $row = 2;
        foreach ($event->jerseySizeOptions() as $size) {
            $sheet->setCellValueExplicit([6, $row], (string) $size, DataType::TYPE_STRING);
            $row++;
        }
        $lastJerseyRow = $row - 1;

        foreach (self::GENDERS as $i => $gender) {
            $sheet->setCellValue([7, 2 + $i], $gender);
        }

        $this->styleHeader($sheet, 'A1:G1', false);
        foreach (['A' => 28, 'B' => 44, 'C' => 28, 'D' => 20, 'E' => 18, 'F' => 14, 'G' => 12] as $col => $width) {
            $sheet->getColumnDimension($col)->setWidth($width);
        }
        $sheet->getStyle('D2:D'.max($lastPackageRow, 2))->getNumberFormat()->setFormatCode('#,##0');

        $range = static fn (string $col, int $last): ?string => $last >= 2
            ? self::SHEET_REFERENCE.'!$'.$col.'$2:$'.$col.'$'.$last
            : null;

        return [
            'bracket' => $range('A', $lastBracketRow),
            'package' => $range('C', $lastPackageRow),
            'jersey' => $range('F', $lastJerseyRow),
            'gender' => self::SHEET_REFERENCE.'!$G$2:$G$'.(1 + count(self::GENDERS)),
        ];
    }

    protected function describeBracket(Bracket $bracket): string
    {
        $parts = [];
        if ($bracket->isRuleTypeAge()) {
            $parts[] = 'Age '.($bracket->age_min ?? 0).'-'.($bracket->age_max ?? '∞')
                .($bracket->age_ref_date ? ' on '.$bracket->age_ref_date->format('Y-m-d') : '');
        } elseif ($bracket->isRuleTypeBirthYear()) {
            $parts[] = 'Born '.($bracket->birth_year_start ?? '…').'-'.($bracket->birth_year_end ?? '…');
        }
        if (in_array($bracket->gender_rule, [Bracket::GENDER_BOYS, Bracket::GENDER_GIRLS], true)) {
            $parts[] = ucfirst($bracket->gender_rule).' only';
        }

        return $parts === [] ? 'Open' : implode(', ', $parts);
    }

    /**
     * @param  array{bracket: ?string, package: ?string, jersey: ?string, gender: string}  $ranges
     */
    protected function fillData(Worksheet $sheet, array $ranges): void
    {
        $lastRow = RegistrationImportReader::MAX_ROWS + 1;
        $headers = array_keys(self::COLUMNS);
        $sheet->fromArray($headers, null, 'A1');

        $this->styleHeader($sheet, 'A1:L1', true);
        $sheet->freezePane('A2');

        foreach (array_values($headers) as $i => $header) {
            $sheet->getColumnDimensionByColumn($i + 1)->setWidth(match ($header) {
                'parent_name', 'rider_name', 'team', 'bracket', 'package' => 26,
                'whatsapp', 'dob', 'number_plate' => 16,
                default => 14,
            });
        }

        // Teks murni: menjaga nol di depan nomor WA / plat dan mencegah konversi otomatis.
        foreach (['B', 'H'] as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        }
        $sheet->getStyle("F2:F{$lastRow}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');

        $lists = [
            'G' => ['gender', 'Gender', 'Choose boys, girls or other.'],
            'J' => ['bracket', 'Bracket', 'Choose a bracket from the list (see the Reference sheet).'],
            'K' => ['package', 'Package', 'Choose an active package from the list.'],
            'L' => ['jersey', 'Jersey size', 'Choose a jersey size from the list.'],
        ];
        foreach ($lists as $col => [$key, $title, $message]) {
            if ($ranges[$key] === null) {
                continue;
            }
            $validation = new DataValidation;
            $validation->setType(DataValidation::TYPE_LIST)
                ->setErrorStyle(DataValidation::STYLE_STOP)
                ->setAllowBlank(true)
                ->setShowDropDown(true)
                ->setShowErrorMessage(true)
                ->setErrorTitle($title)
                ->setError($message)
                ->setFormula1($ranges[$key]);
            $sheet->setDataValidation("{$col}2:{$col}{$lastRow}", $validation);
        }
    }

    protected function fillInstructions(Worksheet $sheet, Event $event): void
    {
        $lines = [
            ['Bulk registration import — '.$event->title],
            [''],
            ['How it works'],
            ['1. Fill the "'.self::SHEET_DATA.'" sheet: one row = one rider registered in one bracket. Do not rename or reorder the header row.'],
            ['2. Upload the file on the event\'s registrations page, choose how the registrations should be saved, then review the preview.'],
            ['3. Rows with errors are never imported. Fix them in the file and upload again, or import only the valid rows.'],
            ['4. Maximum '.RegistrationImportReader::MAX_ROWS.' rows per file. Excel (.xlsx) is recommended; .csv is also accepted.'],
            [''],
            ['Columns (* = required)'],
            ['parent_name *', 'Parent / community contact name. Used when a new account is created for the WhatsApp number.'],
            ['whatsapp *', 'WhatsApp number, e.g. 081234567890 or 6281234567890. Several riders may share one number (e.g. the community coordinator).'],
            ['rider_name *', 'Full name of the rider.'],
            ['nickname', 'Optional.'],
            ['pob', 'Place of birth. Optional.'],
            ['dob *', 'Date of birth as yyyy-mm-dd (e.g. 2018-05-21). Real Excel dates are fine too.'],
            ['gender *', 'boys, girls or other (laki-laki / perempuan are also understood).'],
            ['number_plate', 'Optional race number plate, max 50 characters.'],
            ['team *', 'Team / community name. Separate several teams with ";". Teams that do not exist yet are created.'],
            ['bracket *', 'Exact bracket name from the Reference sheet. The rider must meet the bracket\'s age / birth-year and gender rule.'],
            ['package *', 'Exact name of an active package from the Reference sheet.'],
            ['jersey_size', 'Required only when the package includes a jersey. Sizes are listed in the Reference sheet.'],
            [''],
            ['Notes'],
            ['• A rider that already exists for the same WhatsApp number (same name and date of birth) is reused instead of duplicated.'],
            ['• A rider cannot be registered twice in the same bracket, and bracket / package quotas are enforced.'],
            ['• Notifications (e-ticket or payment link) are sent after each registration is saved, using the standard messages.'],
        ];
        foreach ($lines as $i => $line) {
            foreach ($line as $j => $text) {
                $sheet->setCellValueExplicit([$j + 1, $i + 1], $text, DataType::TYPE_STRING);
            }
        }
        $sheet->getColumnDimension('A')->setWidth(34);
        $sheet->getColumnDimension('B')->setWidth(110);
        foreach ([1, 3, 9, 22] as $bold) {
            $sheet->getStyle("A{$bold}")->getFont()->setBold(true);
        }
        $sheet->getStyle('A1')->getFont()->setSize(14);
        $sheet->getStyle('B1:B'.count($lines))->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
    }

    protected function styleHeader(Worksheet $sheet, string $range, bool $markRequired): void
    {
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEA580C');

        if (! $markRequired) {
            return;
        }

        // Kolom opsional diberi warna abu-abu supaya wajib/tidak wajib terlihat jelas.
        foreach (array_values(self::COLUMNS) as $i => $required) {
            if ($required) {
                continue;
            }
            $sheet->getStyle([$i + 1, 1])->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF71717A');
        }
    }
}
