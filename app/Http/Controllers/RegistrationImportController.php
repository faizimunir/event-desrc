<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\RegistrationImport\RegistrationImportException;
use App\Services\RegistrationImport\RegistrationImportReader;
use App\Services\RegistrationImport\RegistrationImportService;
use App\Services\RegistrationImport\RegistrationImportTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin: bulk registrasi lewat file Excel/CSV (pendaftaran manual oleh admin atau dikelola satu komunitas).
 * Alur: download template → upload → preview validasi → konfirmasi → simpan + kirim notifikasi.
 */
class RegistrationImportController extends Controller
{
    private const DISK = 'local';

    private const DIRECTORY = 'registration-imports';

    /** File upload yang tidak pernah dikonfirmasi dibersihkan setelah (jam). */
    private const STALE_AFTER_HOURS = 24;

    public function __construct(
        protected RegistrationImportReader $reader,
        protected RegistrationImportService $service,
    ) {}

    /** Halaman upload (dan hasil import terakhir). */
    public function create(Event $event)
    {
        $this->authorizeImport($event);

        return view('registrations.import', [
            'event' => $event,
            'maxRows' => RegistrationImportReader::MAX_ROWS,
            'deadlineOptions' => RegistrationImportService::DEADLINE_OPTIONS_MINUTES,
            'defaultDeadline' => RegistrationImportService::DEFAULT_DEADLINE_MINUTES,
        ]);
    }

    public function template(Event $event): StreamedResponse|RedirectResponse
    {
        $this->authorizeImport($event);

        if (! RegistrationImportTemplate::excelSupported()) {
            return redirect()->route('events.registrations.import.create', $event)
                ->with('error', __('This server cannot create Excel files (the PHP "zip" extension is not enabled). Ask your administrator to enable it.'));
        }

        $spreadsheet = (new RegistrationImportTemplate)->build($event);
        $filename = 'registration-import-template-'.str($event->slug ?: $event->title)->slug().'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /** Simpan file upload sementara lalu arahkan ke halaman preview. */
    public function preview(Request $request, Event $event): RedirectResponse
    {
        $this->authorizeImport($event);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:5120', 'extensions:xlsx,csv'],
            'mode' => ['required', Rule::in(RegistrationImportService::MODES)],
            'deadline_minutes' => ['nullable', 'integer', Rule::in(RegistrationImportService::DEADLINE_OPTIONS_MINUTES)],
        ]);

        $this->purgeStaleUploads();

        $extension = strtolower($request->file('file')->getClientOriginalExtension());
        $token = strtolower((string) Str::ulid());
        $request->file('file')->storeAs(self::DIRECTORY, $token.'.'.$extension, self::DISK);

        $request->session()->put($this->sessionKey($event, $token), [
            'extension' => $extension,
            'mode' => $validated['mode'],
            'deadline_minutes' => (int) ($validated['deadline_minutes'] ?? RegistrationImportService::DEFAULT_DEADLINE_MINUTES),
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('events.registrations.import.show', [$event, $token]);
    }

    /** Preview hasil validasi (tidak menyimpan apa pun). */
    public function show(Request $request, Event $event, string $token)
    {
        $this->authorizeImport($event);
        $pending = $this->pendingUpload($request, $event, $token);

        try {
            $rows = $this->reader->read($this->path($token, $pending['extension']), $pending['extension'], $event->id);
            $report = $this->service->validate($event, $rows, $pending['mode']);
        } catch (RegistrationImportException $e) {
            $this->discardUpload($request, $event, $token, $pending['extension']);

            return redirect()->route('events.registrations.import.create', $event)->with('error', $e->getMessage());
        }

        return view('registrations.import-preview', [
            'event' => $event,
            'token' => $token,
            'report' => $report,
            'mode' => $pending['mode'],
            'deadlineMinutes' => $pending['deadline_minutes'],
        ]);
    }

    /** Konfirmasi: validasi ulang lalu simpan baris valid dan kirim notifikasi. */
    public function store(Request $request, Event $event, string $token): RedirectResponse
    {
        $this->authorizeImport($event);
        $pending = $this->pendingUpload($request, $event, $token);
        $showUrl = route('events.registrations.import.show', [$event, $token]);

        $lock = Cache::lock('registration-import:'.$token, 600);
        if (! $lock->get()) {
            return redirect($showUrl)->with('error', __('This import is already being processed.'));
        }

        try {
            set_time_limit(300);

            $rows = $this->reader->read($this->path($token, $pending['extension']), $pending['extension'], $event->id);
            $result = $this->service->import(
                $event,
                $rows,
                $pending['mode'],
                $request->user(),
                $request->boolean('skip_invalid'),
                $pending['deadline_minutes'],
            );
        } catch (RegistrationImportException $e) {
            return redirect($showUrl)->with('error', $e->getMessage());
        } finally {
            $lock->release();
        }

        $this->discardUpload($request, $event, $token, $pending['extension']);

        $imported = count($result['imported']);
        $message = trans_choice(':count registration was saved.|:count registrations were saved.', $imported, ['count' => $imported]);
        if ($result['failed'] !== []) {
            $message .= ' '.__(':failed row(s) could not be saved. See the details below.', ['failed' => count($result['failed'])]);
        }

        return redirect()->route('events.registrations.import.create', $event)
            ->with($imported > 0 ? 'status' : 'error', $imported > 0 ? $message : __('No registrations were saved.').' '.$message)
            ->with('import_result', $result + ['mode' => $pending['mode']]);
    }

    private function authorizeImport(Event $event): void
    {
        abort_unless(auth()->user()->canAs('event.update'), 403);
        $this->authorize('update', $event);
    }

    private function sessionKey(Event $event, string $token): string
    {
        return "registration_import.{$event->id}.{$token}";
    }

    /** @return array{extension: string, mode: string, deadline_minutes: int, user_id: int} */
    private function pendingUpload(Request $request, Event $event, string $token): array
    {
        $pending = $request->session()->get($this->sessionKey($event, $token));

        abort_unless(
            is_array($pending)
                && ($pending['user_id'] ?? null) === $request->user()->id
                && Storage::disk(self::DISK)->exists(self::DIRECTORY.'/'.$token.'.'.$pending['extension']),
            404
        );

        return $pending;
    }

    private function path(string $token, string $extension): string
    {
        return Storage::disk(self::DISK)->path(self::DIRECTORY.'/'.$token.'.'.$extension);
    }

    private function discardUpload(Request $request, Event $event, string $token, string $extension): void
    {
        Storage::disk(self::DISK)->delete(self::DIRECTORY.'/'.$token.'.'.$extension);
        $request->session()->forget($this->sessionKey($event, $token));
    }

    private function purgeStaleUploads(): void
    {
        $disk = Storage::disk(self::DISK);
        $threshold = now()->subHours(self::STALE_AFTER_HOURS)->getTimestamp();

        foreach ($disk->files(self::DIRECTORY) as $file) {
            if ($disk->lastModified($file) < $threshold) {
                $disk->delete($file);
            }
        }
    }
}
