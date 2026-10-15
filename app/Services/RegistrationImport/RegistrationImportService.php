<?php

namespace App\Services\RegistrationImport;

use App\Models\Bracket;
use App\Models\Event;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\Team;
use App\Models\User;
use App\Services\PaymentLinkNotifier;
use App\Services\QuotaReservationService;
use App\Services\RegistrationEligibilityService;
use App\Services\RiderSimilarityService;
use App\Services\TicketService;
use App\Services\WhacenterService;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Validasi dan penyimpanan bulk registrasi. Aturan validasi sama dengan form registrasi
 * (eligibility umur/tahun lahir/gender, kuota bracket & paket, duplikat rider+bracket).
 *
 * Mode penyimpanan (dipilih saat upload):
 * - paid   : dibayar di luar sistem (komunitas / admin) → registrasi approved, order paid,
 *            payment success, tiket terbit. Notifikasi = e-ticket (alur TicketService).
 * - unpaid : menunggu pembayaran → registrasi pending, order unpaid dengan batas waktu.
 *            Notifikasi = payment link (alur PaymentLinkNotifier).
 *
 * Notifikasi dikirim per baris setelah baris tersebut berhasil tersimpan (commit).
 */
class RegistrationImportService
{
    public const MODE_PAID = 'paid';

    public const MODE_UNPAID = 'unpaid';

    public const MODES = [self::MODE_PAID, self::MODE_UNPAID];

    /** Pilihan batas waktu pembayaran (menit) untuk mode unpaid. */
    public const DEADLINE_OPTIONS_MINUTES = [60, 360, 1440, 4320, 10080];

    public const DEFAULT_DEADLINE_MINUTES = 1440;

    private const GENDER_ALIASES = [
        'boys' => 'boys', 'boy' => 'boys', 'male' => 'boys', 'laki-laki' => 'boys', 'laki laki' => 'boys',
        'laki' => 'boys', 'putra' => 'boys', 'pria' => 'boys', 'l' => 'boys',
        'girls' => 'girls', 'girl' => 'girls', 'female' => 'girls', 'perempuan' => 'girls',
        'putri' => 'girls', 'wanita' => 'girls', 'p' => 'girls',
        'other' => 'other', 'lainnya' => 'other', 'lain' => 'other',
    ];

    public function __construct(
        protected RegistrationEligibilityService $eligibility,
        protected RiderSimilarityService $similarity,
    ) {}

    /**
     * Validasi semua baris tanpa menulis apa pun ke database.
     *
     * @param  list<array{row: int, data: array<string, ?string>}>  $rows
     * @return array{
     *     rows: list<array{row: int, data: array<string, ?string>, errors: list<string>, warnings: list<string>, normalized: ?array<string, mixed>}>,
     *     valid: int,
     *     invalid: int,
     *     new_teams: list<string>
     * }
     *
     * @throws RegistrationImportException
     */
    public function validate(Event $event, array $rows, string $mode): array
    {
        $this->assertModeAllowed($event, $mode);

        $event->loadMissing('packages.rewards');
        $brackets = $event->brackets()->with('event')->get();
        $packages = $event->packages;
        $jerseySizes = $event->jerseySizeOptions();

        $bracketLeft = [];
        $packageLeft = [];
        $seen = [];
        $knownTeams = $this->existingTeamNames($rows);
        $newTeams = [];
        $result = [];

        foreach ($rows as $row) {
            $errors = [];
            $warnings = [];
            $n = $this->normalizeRow($row['data'], $errors);

            // Bracket
            $bracket = null;
            if ($row['data']['bracket'] !== null) {
                $matches = $brackets->filter(fn (Bracket $b) => $this->key($b->name) === $this->key($row['data']['bracket']));
                if ($matches->count() === 1) {
                    $bracket = $matches->first();
                } elseif ($matches->count() > 1) {
                    $errors[] = __('Bracket ":name" matches more than one bracket in this event.', ['name' => $row['data']['bracket']]);
                } else {
                    $errors[] = __('Bracket ":name" was not found in this event.', ['name' => $row['data']['bracket']]);
                }
            } else {
                $errors[] = __('Bracket is required.');
            }

            // Package
            $package = null;
            if ($row['data']['package'] !== null) {
                $matches = $packages->filter(fn (Package $p) => $this->key($p->name) === $this->key($row['data']['package']));
                $active = $matches->filter(fn (Package $p) => $p->isActive());
                if ($active->count() === 1) {
                    $package = $active->first();
                } elseif ($active->count() > 1) {
                    $errors[] = __('Package ":name" matches more than one package in this event.', ['name' => $row['data']['package']]);
                } elseif ($matches->isNotEmpty()) {
                    $errors[] = __('Package ":name" is not active.', ['name' => $row['data']['package']]);
                } else {
                    $errors[] = __('Package ":name" was not found in this event.', ['name' => $row['data']['package']]);
                }
            } else {
                $errors[] = __('Package is required.');
            }

            // Jersey
            $jersey = null;
            if ($package) {
                if ($package->hasJerseyReward()) {
                    $match = collect($jerseySizes)->first(fn ($size) => $this->key((string) $size) === $this->key((string) $row['data']['jersey_size']));
                    if ($row['data']['jersey_size'] === null) {
                        $errors[] = __('Jersey size is required for this package.');
                    } elseif ($match === null) {
                        $errors[] = __('Jersey size ":size" is not available. Choose one of: :sizes.', ['size' => $row['data']['jersey_size'], 'sizes' => implode(', ', $jerseySizes)]);
                    } else {
                        $jersey = (string) $match;
                    }
                } elseif ($row['data']['jersey_size'] !== null) {
                    $warnings[] = __('Jersey size is ignored because the package has no jersey.');
                }
            }

            // Teams
            $teamNames = [];
            foreach (preg_split('/\s*;\s*/u', (string) $row['data']['team']) ?: [] as $name) {
                $name = trim($name);
                if ($name === '') {
                    continue;
                }
                if (mb_strlen($name) > 255) {
                    $errors[] = __('Team name is too long (max 255 characters).');

                    continue;
                }
                $teamNames[$this->key($name)] ??= $name;
            }
            $teamNames = array_values($teamNames);
            if ($teamNames === []) {
                $errors[] = __('At least one team is required.');
            }

            // Rider / duplicates / eligibility (hanya jika data dasar valid)
            if ($errors === [] && $bracket && $package) {
                $existing = $this->findExistingRider($n['whatsapp'], $n['rider_name'], $n['dob']);

                if ($existing && Registration::query()
                    ->where('event_id', $event->id)
                    ->where('rider_id', $existing->id)
                    ->where('bracket_id', $bracket->id)
                    ->exists()) {
                    $errors[] = __('This rider is already registered in this bracket.');
                }

                $seenKey = implode('|', [$n['whatsapp'], $this->key($n['rider_name']), $n['dob'], $bracket->id]);
                if (isset($seen[$seenKey])) {
                    $errors[] = __('Duplicate of row :row (same rider and bracket).', ['row' => $seen[$seenKey]]);
                }

                $candidate = $existing ?? new Rider([
                    'name' => $n['rider_name'],
                    'dob' => $n['dob'],
                    'gender' => $n['gender'],
                ]);
                $eligibility = $this->eligibility->checkEligibility($candidate, $bracket);
                if (! $eligibility['eligible']) {
                    $errors[] = $eligibility['message'];
                }

                if ($errors === []) {
                    if ($existing) {
                        $warnings[] = __('Existing rider profile will be reused.');
                    } else {
                        $similar = $this->similarity->findSimilarRiders(
                            $n['whatsapp'], $n['rider_name'], $n['nickname'], $n['pob'], $n['dob'], $n['gender'], $n['number_plate']
                        );
                        if ($similar->isNotEmpty()) {
                            $warnings[] = __('Looks similar to existing rider ":name" under this WhatsApp number; a new rider profile will be created.', [
                                'name' => $similar->first()['rider']->name,
                            ]);
                        }
                    }

                    // Kuota kumulatif: baris valid sebelumnya sudah mengambil slot.
                    $bracketLeft[$bracket->id] ??= ['left' => $bracket->remainingQuota()];
                    $packageLeft[$package->id] ??= ['left' => $package->remainingQuota()];
                    if ($bracketLeft[$bracket->id]['left'] !== null && $bracketLeft[$bracket->id]['left'] <= 0) {
                        $errors[] = __('This bracket has no remaining quota.');
                    }
                    if ($packageLeft[$package->id]['left'] !== null && $packageLeft[$package->id]['left'] <= 0) {
                        $errors[] = __('This package has no remaining quota.');
                    }
                    if ($errors === []) {
                        if ($bracketLeft[$bracket->id]['left'] !== null) {
                            $bracketLeft[$bracket->id]['left']--;
                        }
                        if ($packageLeft[$package->id]['left'] !== null) {
                            $packageLeft[$package->id]['left']--;
                        }
                        $seen[$seenKey] = $row['row'];
                        foreach ($teamNames as $name) {
                            if (! isset($knownTeams[$this->key($name)])) {
                                $newTeams[$this->key($name)] ??= $name;
                            }
                        }
                    }
                }
            }

            $result[] = [
                'row' => $row['row'],
                'data' => $row['data'],
                'errors' => $errors,
                'warnings' => $warnings,
                'normalized' => $errors === [] ? array_merge($n, [
                    'teams' => $teamNames,
                    'bracket_id' => $bracket->id,
                    'package_id' => $package->id,
                    'jersey_size' => $jersey,
                ]) : null,
            ];
        }

        $invalid = count(array_filter($result, fn (array $r) => $r['errors'] !== []));

        return [
            'rows' => $result,
            'valid' => count($result) - $invalid,
            'invalid' => $invalid,
            'new_teams' => array_values($newTeams),
        ];
    }

    /**
     * Simpan semua baris valid. Setiap baris disimpan dalam transaksinya sendiri (dengan lock kuota);
     * notifikasi dikirim setelah baris itu berhasil tersimpan.
     *
     * @param  list<array{row: int, data: array<string, ?string>}>  $rows
     * @return array{
     *     imported: list<array{row: int, rider: string, registration_id: int, notified: bool}>,
     *     failed: list<array{row: int, rider: string, reason: string}>,
     *     skipped: int
     * }
     *
     * @throws RegistrationImportException
     */
    public function import(Event $event, array $rows, string $mode, User $admin, bool $skipInvalid = false, ?int $deadlineMinutes = null): array
    {
        $report = $this->validate($event, $rows, $mode);

        if ($report['invalid'] > 0 && ! $skipInvalid) {
            throw new RegistrationImportException(__('Some rows have errors. Fix them or choose to import only the valid rows.'));
        }

        $deadlineMinutes = in_array($deadlineMinutes, self::DEADLINE_OPTIONS_MINUTES, true)
            ? $deadlineMinutes
            : self::DEFAULT_DEADLINE_MINUTES;

        $imported = [];
        $failed = [];

        foreach ($report['rows'] as $row) {
            if ($row['normalized'] === null) {
                continue;
            }

            try {
                [$registration, $order] = $this->storeRow($event, $row['normalized'], $mode, $admin, $deadlineMinutes);
            } catch (RegistrationImportRowException $e) {
                $failed[] = ['row' => $row['row'], 'rider' => $row['normalized']['rider_name'], 'reason' => $e->getMessage()];

                continue;
            } catch (Throwable $e) {
                Log::error('Registration bulk import row failed.', [
                    'event_id' => $event->id,
                    'row' => $row['row'],
                    'exception' => $e,
                ]);
                $failed[] = ['row' => $row['row'], 'rider' => $row['normalized']['rider_name'], 'reason' => __('Unexpected error while saving this row.')];

                continue;
            }

            $imported[] = [
                'row' => $row['row'],
                'rider' => $row['normalized']['rider_name'],
                'registration_id' => $registration->id,
                'notified' => $this->notify($registration, $order, $mode, $deadlineMinutes),
            ];
        }

        return ['imported' => $imported, 'failed' => $failed, 'skipped' => $report['invalid']];
    }

    /**
     * @param  array<string, mixed>  $n
     * @return array{0: Registration, 1: Order}
     */
    protected function storeRow(Event $event, array $n, string $mode, User $admin, int $deadlineMinutes): array
    {
        return QuotaReservationService::withLocks($n['bracket_id'], $n['package_id'], null, function () use ($event, $n, $mode, $admin, $deadlineMinutes) {
            $bracket = Bracket::query()->with('event')->findOrFail($n['bracket_id']);
            $package = Package::query()->findOrFail($n['package_id']);

            if (! $package->isActive()) {
                throw new RegistrationImportRowException(__('This package is not available for registration.'));
            }
            if (! $bracket->hasQuota()) {
                throw new RegistrationImportRowException(__('This bracket has no remaining quota.'));
            }
            if ($package->isQuotaFull()) {
                throw new RegistrationImportRowException(__('This package has no remaining quota.'));
            }

            $user = User::query()->firstOrCreate(
                ['whatsapp' => $n['whatsapp']],
                ['name' => $n['parent_name']]
            );
            if (! $user->hasRole('member')) {
                $user->assignRole('member');
            }

            $rider = $this->findExistingRider($n['whatsapp'], $n['rider_name'], $n['dob'])
                ?? Rider::query()->create([
                    'user_id' => $user->id,
                    'name' => $n['rider_name'],
                    'nickname' => $n['nickname'],
                    'pob' => $n['pob'],
                    'dob' => $n['dob'],
                    'gender' => $n['gender'],
                    'number_plate' => $n['number_plate'],
                ]);

            $eligibility = $this->eligibility->checkEligibility($rider, $bracket);
            if (! $eligibility['eligible']) {
                throw new RegistrationImportRowException($eligibility['message']);
            }

            if (Registration::query()
                ->where('event_id', $event->id)
                ->where('rider_id', $rider->id)
                ->where('bracket_id', $bracket->id)
                ->exists()) {
                throw new RegistrationImportRowException(__('This rider is already registered in this bracket.'));
            }

            $teamIds = collect($n['teams'])->map(fn (string $name) => $this->resolveTeam($name)->id)->unique()->values()->all();
            $rider->teams()->syncWithoutDetaching($teamIds);

            $paid = $mode === self::MODE_PAID;

            $registration = Registration::query()->create([
                'event_id' => $event->id,
                'rider_id' => $rider->id,
                'team_ids' => $teamIds,
                'bracket_id' => $bracket->id,
                'package_id' => $package->id,
                'status' => $paid ? Registration::STATUS_APPROVED : Registration::STATUS_PENDING,
                'number_plate' => $n['number_plate'],
                'jersey_size' => $n['jersey_size'],
            ]);

            $order = Order::query()->create([
                'registration_id' => $registration->id,
                'user_id' => $user->id,
                'status' => $paid ? Order::STATUS_PAID : Order::STATUS_UNPAID,
                'confirmed_at' => now(),
                'paid_at' => $paid ? now() : null,
                'expired_at' => $paid ? null : now()->addMinutes($deadlineMinutes),
            ]);

            if ($paid) {
                $amount = round((float) $package->price, 2);
                $adminFee = $package->adminFeeIsIncludedInPrice() ? 0.0 : round((float) $package->admin_fee, 2);

                $order->payments()->create([
                    'registration_id' => $registration->id,
                    'amount' => $amount,
                    'admin_fee_amount' => $adminFee,
                    'unique_code' => null,
                    'transfer_amount' => round($amount + $adminFee, 2),
                    'method' => Payment::METHOD_MANUAL,
                    'status' => Payment::STATUS_SUCCESS,
                    'paid_at' => now(),
                    'reviewed_at' => now(),
                    'reviewed_by' => $admin->id,
                    'admin_notes' => __('Added via bulk import'),
                ]);
            }
            // Mode unpaid: percobaan bayar dibuat saat peserta membuka payment link (alur standar).

            return [$registration, $order];
        });
    }

    /**
     * Kirim notifikasi sesuai alur yang sudah ada. Kegagalan kirim tidak membatalkan registrasi.
     */
    protected function notify(Registration $registration, Order $order, string $mode, int $deadlineMinutes): bool
    {
        try {
            if ($mode === self::MODE_PAID) {
                TicketService::ensureTicketForRegistration($registration->fresh());
            } else {
                PaymentLinkNotifier::send($order->fresh(), $registration->fresh(), $deadlineMinutes);
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Registration bulk import notification failed.', [
                'registration_id' => $registration->id,
                'mode' => $mode,
                'exception' => $e,
            ]);

            return false;
        }
    }

    protected function assertModeAllowed(Event $event, string $mode): void
    {
        if (! in_array($mode, self::MODES, true)) {
            throw new RegistrationImportException(__('Invalid save mode.'));
        }

        if ($mode === self::MODE_UNPAID && ! $event->allowsManualPayment() && ! $event->allowsQrisPayment()) {
            throw new RegistrationImportException(__('This event has no payment method configured, so registrations cannot be saved as "waiting for payment". Choose "Already paid" instead.'));
        }
    }

    /**
     * Normalisasi + validasi field rider/orang tua dari baris mentah.
     *
     * @param  array<string, ?string>  $data
     * @param  list<string>  $errors
     * @return array{parent_name: string, whatsapp: string, rider_name: string, nickname: ?string, pob: ?string, dob: string, gender: string, number_plate: ?string}
     */
    protected function normalizeRow(array $data, array &$errors): array
    {
        $n = [
            'parent_name' => (string) $data['parent_name'],
            'whatsapp' => '',
            'rider_name' => (string) $data['rider_name'],
            'nickname' => $data['nickname'],
            'pob' => $data['pob'],
            'dob' => '',
            'gender' => '',
            'number_plate' => $data['number_plate'],
        ];

        foreach (['parent_name' => 'Parent name', 'rider_name' => 'Rider name'] as $field => $label) {
            if ($data[$field] === null) {
                $errors[] = __(':field is required.', ['field' => $label]);
            }
        }
        foreach (['parent_name' => 255, 'rider_name' => 255, 'nickname' => 255, 'pob' => 255, 'number_plate' => 50] as $field => $max) {
            if ($data[$field] !== null && mb_strlen($data[$field]) > $max) {
                $errors[] = __(':field is too long (max :max characters).', ['field' => str_replace('_', ' ', ucfirst($field)), 'max' => $max]);
            }
        }

        if ($data['whatsapp'] === null) {
            $errors[] = __('WhatsApp number is required.');
        } else {
            $digits = preg_replace('/\D/', '', $data['whatsapp']);
            $normalized = $digits === '' ? '' : WhacenterService::normalizeWhatsApp($data['whatsapp']);
            if (strlen($digits) < 8 || strlen($normalized) > 20) {
                $errors[] = __('WhatsApp number ":number" is not valid.', ['number' => $data['whatsapp']]);
            } else {
                $n['whatsapp'] = $normalized;
            }
        }

        if ($data['dob'] === null) {
            $errors[] = __('Date of birth is required.');
        } else {
            $dob = $this->parseDate($data['dob']);
            if ($dob === null) {
                $errors[] = __('Date of birth ":value" is not a valid date (use yyyy-mm-dd).', ['value' => $data['dob']]);
            } elseif ($dob > new DateTimeImmutable('today') || (int) $dob->format('Y') < 1900) {
                $errors[] = __('Date of birth ":value" is not allowed.', ['value' => $data['dob']]);
            } else {
                $n['dob'] = $dob->format('Y-m-d');
            }
        }

        if ($data['gender'] === null) {
            $errors[] = __('Gender is required.');
        } else {
            $gender = self::GENDER_ALIASES[$this->key($data['gender'])] ?? null;
            if ($gender === null) {
                $errors[] = __('Gender ":value" is not valid. Use boys, girls or other.', ['value' => $data['gender']]);
            } else {
                $n['gender'] = $gender;
            }
        }

        return $n;
    }

    protected function parseDate(string $value): ?DateTimeImmutable
    {
        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'Y/m/d'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date;
            }
        }

        return null;
    }

    /** Rider milik user dengan nomor WA ini yang namanya + tanggal lahirnya sama persis. */
    protected function findExistingRider(string $whatsapp, string $name, string $dob): ?Rider
    {
        if ($whatsapp === '' || $dob === '') {
            return null;
        }

        $user = User::query()->where('whatsapp', $whatsapp)->first();
        if (! $user) {
            return null;
        }

        return Rider::query()
            ->where('user_id', $user->id)
            ->whereDate('dob', $dob)
            ->get()
            ->first(fn (Rider $rider) => $this->key((string) $rider->name) === $this->key($name));
    }

    protected function resolveTeam(string $name): Team
    {
        return Team::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?? Team::query()->create(['name' => $name]);
    }

    /**
     * @param  list<array{row: int, data: array<string, ?string>}>  $rows
     * @return array<string, true>
     */
    protected function existingTeamNames(array $rows): array
    {
        $names = [];
        foreach ($rows as $row) {
            foreach (preg_split('/\s*;\s*/u', (string) $row['data']['team']) ?: [] as $name) {
                if (trim($name) !== '') {
                    $names[mb_strtolower(trim($name))] = true;
                }
            }
        }

        $known = [];
        foreach (array_chunk(array_keys($names), 500) as $chunk) {
            Team::query()
                ->whereRaw('LOWER(name) IN ('.implode(',', array_fill(0, count($chunk), '?')).')', $chunk)
                ->pluck('name')
                ->each(function (string $name) use (&$known): void {
                    $known[$this->key($name)] = true;
                });
        }

        return $known;
    }

    /** Kunci pembanding: huruf kecil, spasi dirapikan. */
    protected function key(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $value)));
    }
}
