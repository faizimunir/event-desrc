@if (session('status'))
    <flux:callout variant="success" class="rounded-lg mb-4">{{ session('status') }}</flux:callout>
@endif
@if (session('error'))
    <flux:callout variant="danger" class="rounded-lg mb-4">{{ session('error') }}</flux:callout>
@endif

@canAs('manage_live_results')
    <div class="mb-6">
        <form method="POST" action="{{ route('events.live-result.flag', $event) }}" class="flex flex-col gap-2 max-w-md">
            @csrf
            <flux:label class="mb-1 block">{{ __('Live Result') }}</flux:label>
            <flux:select name="has_live_result" class="w-full">
                <flux:select.option value="1" :selected="$event->has_live_result">
                    {{ __('Ya, tampilkan di Live Result') }}
                </flux:select.option>
                <flux:select.option value="0" :selected="! $event->has_live_result">
                    {{ __('Tidak pakai Live Result') }}
                </flux:select.option>
            </flux:select>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Jika dinonaktifkan, event ini tidak akan muncul di halaman Live Result publik.') }}
            </p>
            <flux:button type="submit" variant="primary" size="sm" class="mt-2 w-fit">
                {{ __('Simpan Pengaturan') }}
            </flux:button>
        </form>
    </div>
@endcanAs

@if (! $event->has_live_result)
    <flux:callout variant="neutral" class="rounded-lg mb-4">
        {{ __('Fitur Live Result belum diaktifkan untuk event ini. Aktifkan terlebih dahulu di atas untuk mengelola kategori dan menampilkan di halaman publik.') }}
    </flux:callout>
@endif

@if ($event->has_live_result)
@canAs('manage_live_results')
    @if($categories->count() > 0)
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <form method="POST" action="{{ route('events.live-result-categories.sync-all', $event) }}" class="inline">
                @csrf
                <flux:button type="submit" variant="filled" size="sm" icon="arrow-path" class="!bg-green-600 hover:!bg-green-700">
                    {{ __('Sync All') }}
                </flux:button>
            </form>
            <flux:button variant="ghost" size="sm" :href="route('print-center.index')" wire:navigate icon="printer" class="!text-zinc-600 dark:!text-zinc-400">
                {{ __('Print Center') }}
            </flux:button>
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6 mb-6">
        <flux:heading size="lg" class="mb-4">{{ __('Tambah Kategori Baru') }}</flux:heading>
        <form id="live-result-category-form" method="POST" action="{{ route('events.live-result-categories.store', $event) }}" class="space-y-4">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input name="title" type="text" :label="__('Judul Kategori')" :placeholder="__('Contoh: Tournament 2023')" required />
                <div>
                    <flux:label class="mb-2 block">{{ __('Spreadsheet ID') }} <span class="text-red-500">*</span></flux:label>
                    <div class="flex gap-2">
                        <flux:input name="spreadsheet_id" type="text" :placeholder="__('ID dari URL Google Sheets')" class="flex-1" id="spreadsheet_id" required />
                        <flux:button type="button" variant="filled" size="sm" id="fetch-sheets-btn" class="!bg-zinc-600 hover:!bg-zinc-700">
                            {{ __('Fetch Sheets') }}
                        </flux:button>
                    </div>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Contoh: dari URL') }} <code class="bg-zinc-100 dark:bg-zinc-700 px-1 rounded">https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit</code>
                    </p>
                </div>
            </div>

            <div id="fetch-loading" class="hidden flex items-center gap-2 text-blue-600 dark:text-blue-400">
                <flux:icon name="arrow-path" class="size-5 animate-spin" />
                <span>{{ __('Mengambil daftar sheet...') }}</span>
            </div>
            <div id="fetch-error" class="hidden">
                <flux:callout variant="danger" class="rounded-lg"><span id="fetch-error-text"></span></flux:callout>
            </div>
            <div id="sheets-container" class="hidden">
                <flux:label class="mb-2 block">{{ __('Pilih sheet yang akan ditampilkan (round):') }}</flux:label>
                <div id="sheets-checkboxes" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 max-h-60 overflow-y-auto">
                </div>
            </div>

            <flux:button type="submit" variant="primary" icon="plus">{{ __('Tambah Kategori') }}</flux:button>
        </form>
    </div>
@endcanAs

<div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
        <flux:heading size="lg">{{ __('Daftar Kategori') }}</flux:heading>
    </div>

    @if($categories->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        @canAs('manage_live_results')
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Sync') }}</th>
                        @endcanAs
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Print') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Judul') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Spreadsheet ID') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Selected Sheets') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Last Sync') }}</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                        @canAs('event.update')
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Aksi') }}</th>
                        @endcanAs
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                    @foreach($categories as $category)
                        <tr>
                            @canAs('manage_live_results')
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($category->selected_sheets && count($category->selected_sheets) > 0)
                                        <form method="POST" action="{{ route('events.live-result-categories.sync', [$event, $category]) }}" class="inline">
                                            @csrf
                                            <flux:button type="submit" variant="ghost" size="sm" icon="arrow-path" class="!text-green-600 hover:!text-green-700">
                                                {{ __('Sync') }}
                                            </flux:button>
                                        </form>
                                    @else
                                        <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                    @endif
                                </td>
                            @endcanAs
                            <td class="px-6 py-4">
                                @if($category->selected_sheets && count($category->selected_sheets) > 0)
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <select class="print-round-select rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm px-2 py-1.5 min-w-[120px]" data-category-id="{{ $category->id }}" data-event-id="{{ $event->id }}" data-print-url="{{ route('events.live-result-categories.print', [$event, $category]) }}">
                                            @foreach($category->selected_sheets as $sheet)
                                                <option value="{{ $sheet }}">{{ $sheet }}</option>
                                            @endforeach
                                        </select>
                                        <flux:button type="button" variant="ghost" size="sm" class="print-preview-btn !text-orange-600 hover:!text-orange-700" icon="printer" data-category-id="{{ $category->id }}">
                                            {{ __('Print') }}
                                        </flux:button>
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $category->title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-zinc-500 dark:text-zinc-400">{{ \Illuminate\Support\Str::limit($category->spreadsheet_id, 28) }}</td>
                            <td class="px-6 py-4">
                                @if($category->selected_sheets && count($category->selected_sheets) > 0)
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($category->selected_sheets as $sheet)
                                            <flux:badge color="zinc" size="sm">{{ $sheet }}</flux:badge>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-sm text-zinc-400 dark:text-zinc-500 italic">{{ __('Belum dipilih') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $category->last_sync ? $category->last_sync->format('d M Y H:i') : __('Belum pernah') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($category->is_active)
                                    <flux:badge color="green" size="sm">{{ __('Aktif') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('Nonaktif') }}</flux:badge>
                                @endif
                            </td>
                            @canAs('event.update')
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <form method="POST" action="{{ route('events.live-result-categories.destroy', [$event, $category]) }}" class="inline" onsubmit="return confirm('{{ __('Apakah Anda yakin ingin menghapus kategori ini?') }}');">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" variant="ghost" size="sm" color="red" icon="trash">
                                            {{ __('Hapus') }}
                                        </flux:button>
                                    </form>
                                </td>
                            @endcanAs
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="px-6 py-8 text-center">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada kategori. Silakan tambahkan kategori baru di atas.') }}</p>
        </div>
    @endif
</div>

@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.print-preview-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var row = this.closest('tr');
            var select = row ? row.querySelector('.print-round-select') : null;
            var url = select ? select.getAttribute('data-print-url') : null;
            var round = select ? select.value : '';
            if (url && round) {
                window.open(url + '?round=' + encodeURIComponent(round), '_blank', 'noopener');
            }
        });
    });
});
</script>
@canAs('manage_live_results')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fetchBtn = document.getElementById('fetch-sheets-btn');
    const spreadsheetInput = document.getElementById('spreadsheet_id');
    const loadingEl = document.getElementById('fetch-loading');
    const errorEl = document.getElementById('fetch-error');
    const errorText = document.getElementById('fetch-error-text');
    const container = document.getElementById('sheets-container');
    const checkboxesEl = document.getElementById('sheets-checkboxes');

    if (!fetchBtn || !spreadsheetInput) return;

    fetchBtn.addEventListener('click', function() {
        const spreadsheetId = spreadsheetInput.value.trim();
        if (!spreadsheetId) {
            alert('{{ __("Silakan masukkan Spreadsheet ID terlebih dahulu") }}');
            return;
        }

        loadingEl?.classList.remove('hidden');
        errorEl?.classList.add('hidden');
        container?.classList.add('hidden');
        fetchBtn.disabled = true;

        fetch('{{ route("events.live-result-categories.fetch-sheets", $event) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ spreadsheet_id: spreadsheetId })
        })
        .then(r => r.json())
        .then(data => {
            loadingEl?.classList.add('hidden');
            fetchBtn.disabled = false;
            if (data.success && data.sheets && data.sheets.length > 0) {
                checkboxesEl.innerHTML = '';
                data.sheets.forEach(function(sheet) {
                    const label = document.createElement('label');
                    label.className = 'flex items-center gap-2 cursor-pointer';
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.name = 'selected_sheets[]';
                    checkbox.value = sheet;
                    checkbox.className = 'rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500';
                    const span = document.createElement('span');
                    span.className = 'text-sm text-zinc-700 dark:text-zinc-300';
                    span.textContent = sheet;
                    label.appendChild(checkbox);
                    label.appendChild(span);
                    checkboxesEl.appendChild(label);
                });
                container?.classList.remove('hidden');
            } else {
                errorText.textContent = data.error || '{{ __("Tidak ada sheet ditemukan di spreadsheet ini.") }}';
                errorEl?.classList.remove('hidden');
            }
        })
        .catch(function() {
            loadingEl?.classList.add('hidden');
            fetchBtn.disabled = false;
            errorText.textContent = '{{ __("Terjadi kesalahan saat mengambil data.") }}';
            errorEl?.classList.remove('hidden');
        });
    });
});
</script>
@endcanAs
