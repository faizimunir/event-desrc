@php
    $category = $category ?? null;
    $selectedSheets = old('selected_sheets', $category?->selected_sheets ?? []);
    $selectedSheets = is_array($selectedSheets) ? $selectedSheets : [];
@endphp

<form
    id="live-result-category-form"
    method="POST"
    action="{{ $category ? route('events.live-result-categories.update', [$event, $category]) : route('events.live-result-categories.store', $event) }}"
    class="max-w-lg space-y-4"
>
    @csrf
    @if ($category)
        @method('PUT')
    @endif

    <flux:input
        name="title"
        type="text"
        :label="__('Judul Kategori')"
        :value="old('title', $category?->title)"
        :placeholder="__('Contoh: Tournament 2023')"
        required
        autofocus
    />
    @error('title')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
    @enderror

    <div>
        <flux:label class="mb-2 block">{{ __('Spreadsheet ID') }} <span class="text-red-500">*</span></flux:label>
        <div class="flex gap-2">
            <flux:input
                name="spreadsheet_id"
                type="text"
                :value="old('spreadsheet_id', $category?->spreadsheet_id)"
                :placeholder="__('ID dari URL Google Sheets')"
                class="min-w-0 flex-1"
                id="spreadsheet_id"
                required
            />
            <flux:button type="button" variant="outline" square icon="arrow-path" id="fetch-sheets-btn" :aria-label="__('Fetch Sheets')" />
        </div>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            {{ __('Contoh: dari URL') }} <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-700">https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit</code>
        </p>
        @error('spreadsheet_id')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div id="fetch-loading" class="hidden flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
        <flux:icon name="arrow-path" class="size-4 animate-spin" />
        <span>{{ __('Mengambil daftar sheet...') }}</span>
    </div>
    <div id="fetch-error" class="hidden">
        <flux:callout variant="danger" class="rounded-lg"><span id="fetch-error-text"></span></flux:callout>
    </div>

    <div id="sheets-container" class="{{ $selectedSheets !== [] ? '' : 'hidden' }}">
        <flux:label class="mb-2 block">{{ __('Pilih sheet yang akan ditampilkan (round):') }}</flux:label>
        <div id="sheets-checkboxes" class="grid max-h-60 grid-cols-2 gap-3 overflow-y-auto rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50 sm:grid-cols-3">
            @foreach ($selectedSheets as $sheet)
                <label class="flex cursor-pointer items-center gap-2">
                    <input type="checkbox" name="selected_sheets[]" value="{{ $sheet }}" checked class="rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500">
                    <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $sheet }}</span>
                </label>
            @endforeach
        </div>
        @error('selected_sheets')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
    </div>

    @if ($category)
        <input type="hidden" name="is_active" value="0">
        <flux:checkbox name="is_active" value="1" :checked="old('is_active', $category->is_active)" :label="__('Aktif')" />
    @endif

    <div class="flex gap-2">
        <flux:button type="submit" variant="primary">
            {{ $category ? __('Update kategori') : __('Tambah Kategori') }}
        </flux:button>
        <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'live-result'])" wire:navigate>{{ __('Cancel') }}</flux:button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fetchBtn = document.getElementById('fetch-sheets-btn');
    const spreadsheetInput = document.getElementById('spreadsheet_id');
    const loadingEl = document.getElementById('fetch-loading');
    const errorEl = document.getElementById('fetch-error');
    const errorText = document.getElementById('fetch-error-text');
    const container = document.getElementById('sheets-container');
    const checkboxesEl = document.getElementById('sheets-checkboxes');
    const previouslySelected = @json($selectedSheets);

    if (!fetchBtn || !spreadsheetInput) return;

    fetchBtn.addEventListener('click', function() {
        const spreadsheetId = spreadsheetInput.value.trim();
        if (!spreadsheetId) {
            alert(@json(__('Silakan masukkan Spreadsheet ID terlebih dahulu')));
            return;
        }

        loadingEl?.classList.remove('hidden');
        errorEl?.classList.add('hidden');
        fetchBtn.disabled = true;

        fetch(@json(route('events.live-result-categories.fetch-sheets', $event)), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': @json(csrf_token())
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
                    checkbox.checked = previouslySelected.includes(sheet);
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
                errorText.textContent = data.error || @json(__('Tidak ada sheet ditemukan di spreadsheet ini.'));
                errorEl?.classList.remove('hidden');
            }
        })
        .catch(function() {
            loadingEl?.classList.add('hidden');
            fetchBtn.disabled = false;
            errorText.textContent = @json(__('Terjadi kesalahan saat mengambil data.'));
            errorEl?.classList.remove('hidden');
        });
    });
});
</script>
