<?php

use App\Jobs\SendWhacenterMessageJob;
use App\Models\Bracket;
use App\Models\Event;
use App\Models\Order;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\Rider;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsappNotificationLog;
use App\Services\RegistrationImport\RegistrationImportTemplate;
use App\Services\WhacenterService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
    $this->seed(RolesAndPermissionsSeeder::class);
});

function impUser(string $role = 'admin'): User
{
    $user = User::factory()->create();
    $user->assignRole($role);
    $user->setActiveRole($role);

    return $user;
}

function impEvent(array $attributes = []): Event
{
    return Event::query()->create(array_merge([
        'title' => 'Import Event '.Str::random(4),
        'slug' => 'imp-'.Str::lower(Str::random(8)),
        'start_at' => now()->addMonth(),
        'end_at' => now()->addMonth()->addDay(),
        'status' => Event::STATUS_OPEN_REGIST,
        'payment_methods' => [Event::PAYMENT_MANUAL],
    ], $attributes));
}

function impBracket(Event $event, array $attributes = []): Bracket
{
    return Bracket::query()->create(array_merge(['event_id' => $event->id, 'name' => 'Open'], $attributes));
}

function impPackage(Event $event, array $attributes = []): Package
{
    return Package::query()->create(array_merge([
        'event_id' => $event->id,
        'name' => 'Paket A',
        'price' => 100000,
        'admin_fee' => 5000,
        'admin_fee_included_in_price' => false,
        'status' => Package::STATUS_ACTIVE,
    ], $attributes));
}

/** @return array<string, string> */
function impRow(array $overrides = []): array
{
    return array_merge([
        'parent_name' => 'Bunda Komunitas',
        'whatsapp' => '081234567890',
        'rider_name' => 'Budi Kecil',
        'nickname' => 'Budi',
        'pob' => 'Jakarta',
        'dob' => '2018-05-05',
        'gender' => 'boys',
        'number_plate' => '12',
        'team' => 'Komunitas A',
        'bracket' => 'Open',
        'package' => 'Paket A',
        'jersey_size' => '',
    ], $overrides);
}

/** @param  list<array<string, string>>  $rows */
function impXlsx(array $rows, ?array $headers = null, ?Event $templateFor = null): UploadedFile
{
    $headers ??= array_keys(RegistrationImportTemplate::COLUMNS);

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet()->setTitle(RegistrationImportTemplate::SHEET_DATA);
    $sheet->fromArray($headers, null, 'A1');
    foreach ($rows as $i => $row) {
        foreach ($headers as $j => $header) {
            if (isset($row[$header]) && $row[$header] !== '') {
                $sheet->setCellValueExplicit([$j + 1, $i + 2], (string) $row[$header], DataType::TYPE_STRING);
            }
        }
    }
    if ($templateFor) {
        $spreadsheet->getProperties()->setCustomProperty(RegistrationImportTemplate::PROPERTY_EVENT_ID, (string) $templateFor->id);
    }

    $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $content = file_get_contents($path);
    @unlink($path);

    return UploadedFile::fake()->createWithContent('registrations.xlsx', $content);
}

/** Upload + buka preview; mengembalikan token. */
function impUpload($test, Event $event, UploadedFile $file, string $mode = 'paid', array $extra = []): string
{
    $response = $test->post(route('events.registrations.import.preview', $event), array_merge(['file' => $file, 'mode' => $mode], $extra));
    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    return basename($response->headers->get('Location'));
}

function impCommit($test, Event $event, string $token, array $data = [])
{
    return $test->post(route('events.registrations.import.store', [$event, $token]), $data);
}

// ---------------------------------------------------------------- template

test('template is an xlsx with the event brackets, packages, jersey sizes and dropdowns', function () {
    $event = impEvent(['jersey_sizes' => ['S', 'M', 'L']]);
    impBracket($event, ['name' => 'U7', 'rule_type' => 'birth_year', 'birth_year_start' => 2018, 'birth_year_end' => 2019, 'gender_rule' => 'boys']);
    impPackage($event, ['name' => 'Early Bird']);
    impPackage($event, ['name' => 'Disabled', 'status' => Package::STATUS_NOT_ACTIVE]);

    $response = $this->actingAs(impUser())->get(route('events.registrations.import.template', $event));
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.xlsx');

    $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
    file_put_contents($path, $response->streamedContent());
    $spreadsheet = IOFactory::load($path);
    @unlink($path);

    expect($spreadsheet->getSheetNames())->toBe(['Registrations', 'Instructions', 'Reference']);

    $data = $spreadsheet->getSheetByName('Registrations');
    foreach (array_keys(RegistrationImportTemplate::COLUMNS) as $i => $header) {
        expect($data->getCell([$i + 1, 1])->getValue())->toBe($header);
    }
    // Tidak ada baris contoh yang ikut ter-import.
    expect($data->getCell('A2')->getValue())->toBeNull();
    expect($data->getCell('B2')->getStyle()->getNumberFormat()->getFormatCode())->toBe('@');
    expect($data->getCell('J2')->getDataValidation()->getFormula1())->toBe('Reference!$A$2:$A$2');
    expect($data->getCell('K2')->getDataValidation()->getFormula1())->toBe('Reference!$C$2:$C$2');

    $reference = $spreadsheet->getSheetByName('Reference');
    expect($reference->getCell('A2')->getValue())->toBe('U7')
        ->and($reference->getCell('B2')->getValue())->toContain('Born 2018-2019')
        ->and($reference->getCell('C2')->getValue())->toBe('Early Bird')
        ->and($reference->getCell('C3')->getValue())->toBeNull()
        ->and($reference->getCell('F4')->getValue())->toBe('L');

    expect((string) $spreadsheet->getProperties()->getCustomPropertyValue(RegistrationImportTemplate::PROPERTY_EVENT_ID))->toBe((string) $event->id);
});

test('import pages require the event update permission', function () {
    $event = impEvent();
    $member = impUser('member');

    $this->actingAs($member)->get(route('events.registrations.import.create', $event))->assertForbidden();
    $this->actingAs($member)->get(route('events.registrations.import.template', $event))->assertForbidden();
    $this->actingAs($member)->post(route('events.registrations.import.preview', $event), ['file' => impXlsx([impRow()]), 'mode' => 'paid'])->assertForbidden();
});

test('import page renders and the registrations tab links to it', function () {
    $event = impEvent();

    $this->actingAs(impUser())->get(route('events.registrations.import.create', $event))
        ->assertOk()
        ->assertSee('Download template')
        ->assertSee('Already paid')
        ->assertSee('Waiting for payment');
});

// ---------------------------------------------------------------- paid mode

test('paid mode saves approved registrations with paid orders, tickets and the standard ticket notification', function () {
    $event = impEvent();
    impBracket($event);
    $package = impPackage($event);
    $this->actingAs($admin = impUser());

    $token = impUpload($this, $event, impXlsx([
        impRow(),
        impRow(['rider_name' => 'Sari Kecil', 'gender' => 'girls', 'team' => 'Komunitas A; Tim Baru', 'dob' => '2017-02-03']),
    ]));

    $this->get(route('events.registrations.import.show', [$event, $token]))
        ->assertOk()->assertSee('Budi Kecil')->assertSee('Sari Kecil')->assertSee('Tim Baru');

    // Preview tidak menyimpan apa pun.
    expect(Registration::count())->toBe(0);
    Queue::assertNothingPushed();

    impCommit($this, $event, $token)
        ->assertRedirect(route('events.registrations.import.create', $event))
        ->assertSessionHas('status')
        ->assertSessionHas('import_result', fn (array $r) => count($r['imported']) === 2 && $r['failed'] === [] && $r['imported'][0]['notified'] === true);

    expect(Registration::count())->toBe(2)
        ->and(User::where('whatsapp', '6281234567890')->count())->toBe(1)
        ->and(Rider::count())->toBe(2)
        ->and(Team::pluck('name')->sort()->values()->all())->toBe(['Komunitas A', 'Tim Baru']);

    $user = User::where('whatsapp', '6281234567890')->first();
    expect($user->name)->toBe('Bunda Komunitas')->and($user->hasRole('member'))->toBeTrue();

    $registration = Registration::with(['rider', 'order', 'payment', 'ticket'])->whereHas('rider', fn ($q) => $q->where('name', 'Sari Kecil'))->first();
    expect($registration->status)->toBe(Registration::STATUS_APPROVED)
        ->and($registration->package_id)->toBe($package->id)
        ->and($registration->team_ids)->toHaveCount(2)
        ->and($registration->rider->teams)->toHaveCount(2)
        ->and($registration->rider->gender)->toBe('girls')
        ->and($registration->order->status)->toBe(Order::STATUS_PAID)
        ->and($registration->order->user_id)->toBe($user->id)
        ->and($registration->order->paid_at)->not->toBeNull()
        ->and($registration->payment->status)->toBe(Payment::STATUS_SUCCESS)
        ->and($registration->payment->method)->toBe(Payment::METHOD_MANUAL)
        ->and((float) $registration->payment->amount)->toBe(100000.0)
        ->and((float) $registration->payment->admin_fee_amount)->toBe(5000.0)
        ->and((float) $registration->payment->transfer_amount)->toBe(105000.0)
        ->and($registration->payment->reviewed_by)->toBe($admin->id)
        ->and($registration->ticket)->not->toBeNull();

    // Notifikasi: e-ticket standar, satu per registrasi, setelah tersimpan.
    expect(Ticket::count())->toBe(2)
        ->and(WhatsappNotificationLog::where('type', WhatsappNotificationLog::TYPE_TICKET_ISSUED)->count())->toBe(2);
    Queue::assertPushed(SendWhacenterMessageJob::class, 2);

    // Kuota terpakai oleh order paid.
    expect(Bracket::first()->registeredCount())->toBe(2);

    // Token sekali pakai.
    impCommit($this, $event, $token)->assertNotFound();
    Storage::disk('local')->assertDirectoryEmpty('registration-imports');
});

// ---------------------------------------------------------------- unpaid mode

test('unpaid mode keeps registrations pending, holds quota and sends the standard payment link', function () {
    $event = impEvent();
    $bracket = impBracket($event, ['quota' => 5]);
    impPackage($event);
    $this->actingAs(impUser());

    $token = impUpload($this, $event, impXlsx([impRow()]), 'unpaid', ['deadline_minutes' => 4320]);
    impCommit($this, $event, $token)->assertSessionHas('status');

    $registration = Registration::with(['order', 'payments', 'ticket'])->firstOrFail();
    expect($registration->status)->toBe(Registration::STATUS_PENDING)
        ->and($registration->order->status)->toBe(Order::STATUS_UNPAID)
        ->and($registration->order->expired_at->between(now()->addMinutes(4319), now()->addMinutes(4321)))->toBeTrue()
        ->and($registration->payments)->toHaveCount(0)
        ->and($registration->ticket)->toBeNull()
        ->and($bracket->fresh()->remainingQuota())->toBe(4);

    expect(WhatsappNotificationLog::where('type', WhatsappNotificationLog::TYPE_PAYMENT_LINK)->count())->toBe(1)
        ->and(WhatsappNotificationLog::where('type', WhatsappNotificationLog::TYPE_TICKET_ISSUED)->count())->toBe(0);
    Queue::assertPushed(SendWhacenterMessageJob::class, fn ($job) => str_contains($job->message, $registration->order->order_code)
        && str_contains($job->message, 'sekitar 3 hari'));
});

test('unpaid mode is refused when the event has no payment method', function () {
    $event = impEvent(['uses_system_registration' => false]);
    impBracket($event);
    impPackage($event);
    $this->actingAs(impUser());

    $token = impUpload($this, $event, impXlsx([impRow()]), 'unpaid');

    $this->get(route('events.registrations.import.show', [$event, $token]))
        ->assertRedirect(route('events.registrations.import.create', $event))
        ->assertSessionHas('error', fn (string $m) => str_contains($m, 'no payment method'));
    expect(Registration::count())->toBe(0);
});

// ---------------------------------------------------------------- validation

test('invalid rows are reported and block the import unless only valid rows are imported', function () {
    $event = impEvent(['jersey_sizes' => ['M', 'L']]);
    impBracket($event, ['name' => 'Open']);
    impBracket($event, ['name' => 'U6', 'rule_type' => 'birth_year', 'birth_year_start' => 2020, 'birth_year_end' => 2021]);
    impBracket($event, ['name' => 'Girls', 'gender_rule' => 'girls', 'rule_type' => 'birth_year', 'birth_year_start' => 2015, 'birth_year_end' => 2020]);
    impPackage($event);
    $jerseyReward = \App\Models\Reward::create(['name' => 'Jersey']);
    $jerseyPackage = impPackage($event, ['name' => 'Paket Jersey']);
    $jerseyPackage->rewards()->attach($jerseyReward->id);
    impPackage($event, ['name' => 'Lama', 'status' => Package::STATUS_NOT_ACTIVE]);
    $this->actingAs(impUser());

    $rows = [
        impRow(['rider_name' => 'Valid Satu']),                                                    // 2: ok
        impRow(['rider_name' => 'Tua', 'bracket' => 'U6']),                                         // 3: too old
        impRow(['rider_name' => 'Cowok', 'bracket' => 'Girls']),                                    // 4: gender
        impRow(['rider_name' => 'Salah Paket', 'package' => 'Tidak Ada']),                          // 5
        impRow(['rider_name' => 'Paket Mati', 'package' => 'Lama']),                                // 6
        impRow(['rider_name' => 'Tanpa Jersey', 'package' => 'Paket Jersey']),                      // 7
        impRow(['rider_name' => 'Jersey Salah', 'package' => 'Paket Jersey', 'jersey_size' => 'XXL']), // 8
        impRow(['rider_name' => 'Tanggal', 'dob' => '31-31-2018']),                                 // 9
        impRow(['rider_name' => 'Masa Depan', 'dob' => now()->addYear()->format('Y-m-d')]),         // 10
        impRow(['rider_name' => 'Gender Aneh', 'gender' => 'robot']),                               // 11
        impRow(['rider_name' => 'WA Salah', 'whatsapp' => '12']),                                   // 12
        impRow(['rider_name' => '', 'team' => '', 'bracket' => '']),                                // 13
        impRow(['rider_name' => 'Valid Satu']),                                                     // 14: duplicate of row 2
        impRow(['rider_name' => 'Valid Jersey', 'package' => 'paket jersey', 'jersey_size' => 'l']),  // 15: ok (case-insensitive)
        impRow(['rider_name' => 'Valid Perempuan', 'bracket' => 'Girls', 'gender' => 'Perempuan']),   // 16: ok (alias)
    ];

    $token = impUpload($this, $event, impXlsx($rows));
    $page = $this->get(route('events.registrations.import.show', [$event, $token]))->assertOk();
    foreach (['Birth year must be', 'for girls only', 'was not found in this event', 'is not active', 'Jersey size is required', 'is not available', 'not a valid date', 'is not allowed', 'is not valid', 'Duplicate of row 2', 'At least one team'] as $message) {
        $page->assertSee($message, false);
    }

    // Tanpa skip_invalid: ditolak dan tidak ada yang tersimpan.
    impCommit($this, $event, $token)
        ->assertRedirect(route('events.registrations.import.show', [$event, $token]))
        ->assertSessionHas('error');
    expect(Registration::count())->toBe(0);

    impCommit($this, $event, $token, ['skip_invalid' => 1])
        ->assertSessionHas('import_result', fn (array $r) => count($r['imported']) === 3 && $r['skipped'] === 12 && $r['failed'] === []);

    expect(Rider::orderBy('name')->pluck('name')->all())->toBe(['Valid Jersey', 'Valid Perempuan', 'Valid Satu']);
    expect(Registration::whereHas('rider', fn ($q) => $q->where('name', 'Valid Jersey'))->first()->jersey_size)->toBe('L');
});

test('quota is enforced cumulatively inside one file', function () {
    $event = impEvent();
    impBracket($event, ['quota' => 1]);
    impPackage($event);
    $this->actingAs(impUser());

    $token = impUpload($this, $event, impXlsx([
        impRow(['rider_name' => 'Pertama']),
        impRow(['rider_name' => 'Kedua']),
    ]));

    $this->get(route('events.registrations.import.show', [$event, $token]))->assertSee('no remaining quota');

    impCommit($this, $event, $token, ['skip_invalid' => 1]);
    expect(Registration::count())->toBe(1)->and(Rider::first()->name)->toBe('Pertama');
});

test('package quota is enforced cumulatively too', function () {
    $event = impEvent();
    impBracket($event);
    impPackage($event, ['quota' => 1]);
    $this->actingAs(impUser());

    $token = impUpload($this, $event, impXlsx([impRow(['rider_name' => 'A']), impRow(['rider_name' => 'B'])]));
    impCommit($this, $event, $token, ['skip_invalid' => 1]);

    expect(Registration::count())->toBe(1);
});

test('an existing rider is reused and an existing registration is rejected', function () {
    $event = impEvent();
    $bracket = impBracket($event);
    $package = impPackage($event);
    $parent = User::factory()->create(['whatsapp' => '6281234567890']);
    $rider = Rider::create(['user_id' => $parent->id, 'name' => 'budi  kecil', 'dob' => '2018-05-05', 'gender' => 'boys']);
    $other = impBracket($event, ['name' => 'Second']);
    Registration::create(['event_id' => $event->id, 'rider_id' => $rider->id, 'bracket_id' => $bracket->id, 'package_id' => $package->id, 'status' => Registration::STATUS_APPROVED]);
    $this->actingAs(impUser());

    $token = impUpload($this, $event, impXlsx([
        impRow(),                              // already registered in Open
        impRow(['bracket' => 'Second']),       // same rider, other bracket -> reuse
    ]));

    $page = $this->get(route('events.registrations.import.show', [$event, $token]));
    $page->assertSee('already registered in this bracket')->assertSee('Existing rider profile will be reused');

    impCommit($this, $event, $token, ['skip_invalid' => 1]);

    expect(Rider::count())->toBe(1)
        ->and(User::where('whatsapp', '6281234567890')->count())->toBe(1)
        ->and(Registration::where('rider_id', $rider->id)->count())->toBe(2)
        ->and(Registration::where('bracket_id', $other->id)->count())->toBe(1);
});

test('a rider similar to an existing one only produces a warning', function () {
    $event = impEvent();
    impBracket($event);
    impPackage($event);
    $parent = User::factory()->create(['whatsapp' => '6281234567890']);
    Rider::create(['user_id' => $parent->id, 'name' => 'Budi Kecil', 'nickname' => 'Budi', 'pob' => 'Jakarta', 'dob' => '2018-05-05', 'gender' => 'boys', 'number_plate' => '12']);
    $this->actingAs(impUser());

    $token = impUpload($this, $event, impXlsx([impRow(['rider_name' => 'Budi Kecill', 'dob' => '2018-05-06'])]));

    $this->get(route('events.registrations.import.show', [$event, $token]))->assertSee('Looks similar to existing rider');
});

// ---------------------------------------------------------------- file handling

test('csv files are accepted, keeping leading zeros and understanding indonesian gender labels', function () {
    $event = impEvent();
    impBracket($event);
    impPackage($event);
    $this->actingAs(impUser());

    $csv = implode("\n", [
        'parent_name;whatsapp;rider_name;nickname;pob;dob;gender;number_plate;team;bracket;package;jersey_size',
        'Bunda;081234567890;Budi Kecil;;;05/05/2018;Laki-laki;007;Komunitas A;open;paket a;',
    ]);
    $file = UploadedFile::fake()->createWithContent('registrations.csv', $csv);

    $token = impUpload($this, $event, $file);
    impCommit($this, $event, $token);

    $registration = Registration::with('rider.user')->firstOrFail();
    expect($registration->rider->gender)->toBe('boys')
        ->and($registration->rider->dob->format('Y-m-d'))->toBe('2018-05-05')
        ->and($registration->number_plate)->toBe('007')
        ->and($registration->rider->user->whatsapp)->toBe('6281234567890');
});

test('numeric excel cells are converted without losing digits', function () {
    $event = impEvent();
    impBracket($event);
    impPackage($event);
    $this->actingAs(impUser());

    $headers = array_keys(RegistrationImportTemplate::COLUMNS);
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet()->setTitle('Registrations');
    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray(['Bunda', 81234567890, 'Budi Kecil', null, null, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new DateTime('2018-05-05')), 'boys', 12, 'Tim', 'Open', 'Paket A', null], null, 'A2');
    $sheet->getStyle('F2')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
    $path = tempnam(sys_get_temp_dir(), 'imp').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);
    $file = UploadedFile::fake()->createWithContent('numbers.xlsx', file_get_contents($path));
    @unlink($path);

    impCommit($this, $event, impUpload($this, $event, $file));

    $registration = Registration::with('rider.user')->firstOrFail();
    expect($registration->rider->user->whatsapp)->toBe('6281234567890')
        ->and($registration->number_plate)->toBe('12')
        ->and($registration->rider->dob->format('Y-m-d'))->toBe('2018-05-05');
});

test('a template downloaded for another event is rejected', function () {
    $event = impEvent();
    $other = impEvent();
    impBracket($event);
    impPackage($event);
    $this->actingAs(impUser());

    $token = impUpload($this, $event, impXlsx([impRow()], null, $other));

    $this->get(route('events.registrations.import.show', [$event, $token]))
        ->assertRedirect(route('events.registrations.import.create', $event))
        ->assertSessionHas('error', fn (string $m) => str_contains($m, 'different event'));
    Storage::disk('local')->assertDirectoryEmpty('registration-imports');
});

test('missing columns, empty files, wrong types and oversized files are rejected', function () {
    $event = impEvent();
    impBracket($event);
    impPackage($event);
    $this->actingAs(impUser());

    $headers = array_values(array_diff(array_keys(RegistrationImportTemplate::COLUMNS), ['dob', 'package']));
    $token = impUpload($this, $event, impXlsx([impRow()], $headers));
    $this->get(route('events.registrations.import.show', [$event, $token]))
        ->assertSessionHas('error', fn (string $m) => str_contains($m, 'dob') && str_contains($m, 'package'));

    $token = impUpload($this, $event, impXlsx([]));
    $this->get(route('events.registrations.import.show', [$event, $token]))
        ->assertSessionHas('error', fn (string $m) => str_contains($m, 'no registration rows'));

    $many = array_fill(0, 501, impRow());
    $token = impUpload($this, $event, impXlsx($many));
    $this->get(route('events.registrations.import.show', [$event, $token]))
        ->assertSessionHas('error', fn (string $m) => str_contains($m, 'too many rows'));

    $this->post(route('events.registrations.import.preview', $event), ['file' => UploadedFile::fake()->create('x.pdf', 10), 'mode' => 'paid'])
        ->assertSessionHasErrors('file');
    $this->post(route('events.registrations.import.preview', $event), ['file' => UploadedFile::fake()->create('big.xlsx', 6000), 'mode' => 'paid'])
        ->assertSessionHasErrors('file');
    $this->post(route('events.registrations.import.preview', $event), ['file' => impXlsx([impRow()]), 'mode' => 'weird'])
        ->assertSessionHasErrors('mode');
    $this->post(route('events.registrations.import.preview', $event), ['file' => impXlsx([impRow()]), 'mode' => 'unpaid', 'deadline_minutes' => 7])
        ->assertSessionHasErrors('deadline_minutes');
    $this->post(route('events.registrations.import.preview', $event), ['mode' => 'paid'])->assertSessionHasErrors('file');
});

test('an upload token cannot be used by another user or another event', function () {
    $event = impEvent();
    impBracket($event);
    impPackage($event);

    $this->actingAs(impUser());
    $token = impUpload($this, $event, impXlsx([impRow()]));

    $this->get(route('events.registrations.import.show', [impEvent(), $token]))->assertNotFound();

    $this->actingAs(impUser('super_admin'));
    $this->get(route('events.registrations.import.show', [$event, $token]))->assertNotFound();
    impCommit($this, $event, $token)->assertNotFound();
    expect(Registration::count())->toBe(0);
});

// ---------------------------------------------------------------- notifications & failures

test('a notification failure never rolls back a saved registration', function () {
    $event = impEvent();
    impBracket($event);
    impPackage($event);
    $this->actingAs(impUser());

    $this->mock(WhacenterService::class, function ($mock) {
        $mock->shouldReceive('queueMessage')->andThrow(new RuntimeException('gateway down'));
    });

    $token = impUpload($this, $event, impXlsx([impRow()]));
    impCommit($this, $event, $token)
        ->assertSessionHas('status')
        ->assertSessionHas('import_result', fn (array $r) => count($r['imported']) === 1 && $r['imported'][0]['notified'] === false);

    expect(Registration::count())->toBe(1)
        ->and(Registration::first()->status)->toBe(Registration::STATUS_APPROVED)
        ->and(Ticket::count())->toBe(1);

    $this->get(route('events.registrations.import.create', $event))->assertOk();
});

test('users with an email also get the standard ticket email', function () {
    \Illuminate\Support\Facades\Mail::fake();
    $event = impEvent();
    impBracket($event);
    impPackage($event);
    User::factory()->create(['whatsapp' => '6281234567890', 'email' => 'parent@example.test']);
    $this->actingAs(impUser());

    impCommit($this, $event, impUpload($this, $event, impXlsx([impRow()])));

    \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\TicketIssuedMail::class, 1);
    Queue::assertPushed(SendWhacenterMessageJob::class, 1);
});

test('quota taken between preview and confirm is re-checked at confirm time', function () {
    $event = impEvent();
    $bracket = impBracket($event, ['quota' => 2]);
    impPackage($event);
    $this->actingAs(impUser());

    $token = impUpload($this, $event, impXlsx([impRow(['rider_name' => 'A']), impRow(['rider_name' => 'B'])]));

    // Slot terakhir diambil orang lain di antara preview dan konfirmasi.
    $other = Rider::create(['user_id' => User::factory()->create()->id, 'name' => 'Sneaky', 'dob' => '2018-01-01', 'gender' => 'boys']);
    $registration = Registration::create(['event_id' => $event->id, 'rider_id' => $other->id, 'bracket_id' => $bracket->id, 'package_id' => Package::first()->id, 'status' => Registration::STATUS_APPROVED]);
    Order::create(['registration_id' => $registration->id, 'status' => Order::STATUS_PAID]);

    impCommit($this, $event, $token, ['skip_invalid' => 1])
        ->assertSessionHas('import_result', fn (array $r) => count($r['imported']) === 1 && $r['skipped'] === 1);

    expect(Registration::count())->toBe(2);
});

test('the result page lists failed rows and notifications that could not be sent', function () {
    $event = impEvent();

    $this->actingAs(impUser())
        ->withSession(['import_result' => [
            'imported' => [['row' => 2, 'rider' => 'Ok Rider', 'registration_id' => 1, 'notified' => false]],
            'failed' => [['row' => 3, 'rider' => 'Bad Rider', 'reason' => 'This bracket has no remaining quota.']],
            'skipped' => 4,
            'mode' => 'paid',
        ]])
        ->get(route('events.registrations.import.create', $event))
        ->assertOk()
        ->assertSee('Import result')
        ->assertSee('Bad Rider')
        ->assertSee('no remaining quota')
        ->assertSee('notification could not be sent');
});

test('payment link notification keeps its standard wording for the regular checkout deadline', function () {
    $event = impEvent();
    impBracket($event);
    $package = impPackage($event);
    $parent = User::factory()->create(['whatsapp' => '6281234567890']);
    $rider = Rider::create(['user_id' => $parent->id, 'name' => 'Rider', 'dob' => '2018-01-01', 'gender' => 'boys']);
    $registration = Registration::create(['event_id' => $event->id, 'rider_id' => $rider->id, 'bracket_id' => Bracket::first()->id, 'package_id' => $package->id, 'status' => Registration::STATUS_PENDING]);
    $order = Order::create(['registration_id' => $registration->id, 'status' => Order::STATUS_UNPAID]);

    \App\Services\PaymentLinkNotifier::send($order, $registration);

    Queue::assertPushed(SendWhacenterMessageJob::class, fn ($job) => str_contains($job->message, 'sekitar 30 menit'));
});
