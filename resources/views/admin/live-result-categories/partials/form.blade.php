@php
    $category = $category ?? null;
    $selectedSheets = old('selected_sheets', $category?->selected_sheets ?? []);
    $selectedSheets = is_array($selectedSheets) ? $selectedSheets : [];
    $usedBracketIds = $event->liveResultCategories()
        ->whereNotNull('bracket_id')
        ->when($category?->id, fn ($q) => $q->where('id', '!=', $category->id))
        ->pluck('bracket_id')
        ->all();
    $availableBrackets = $event->brackets_sorted_for_display
        ->reject(fn ($bracket) => in_array($bracket->id, $usedBracketIds, true))
        ->values();
@endphp

<form
    id="live-result-category-form"
    method="POST"
    action="{{ $category ? route('events.live-result-categories.update', [$event, $category]) : route('events.live-result-categories.store', $event) }}"
    class="max-w-lg space-y-4"
    data-fetch-sheets-url="{{ route('events.live-result-categories.fetch-sheets', $event) }}"
    data-previously-selected="{{ json_encode($selectedSheets) }}"
    data-empty-id-message="{{ __('Silakan masukkan Spreadsheet ID terlebih dahulu') }}"
    data-none-found-message="{{ __('Tidak ada sheet ditemukan di spreadsheet ini.') }}"
    data-error-message="{{ __('Terjadi kesalahan saat mengambil data.') }}"
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
        <flux:select
            name="bracket_id"
            :label="__('Bracket (optional)')"
            :placeholder="__('Pilih bracket…')"
        >
            <option value="">{{ __('— Tanpa bracket —') }}</option>
            @forelse ($availableBrackets as $bracket)
                <option value="{{ $bracket->id }}" @selected((string) old('bracket_id', $category?->bracket_id) === (string) $bracket->id)>
                    {{ $bracket->name }}
                </option>
            @empty
            @endforelse
        </flux:select>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            {{ __('Jika dipilih dan bracket ada di rundown, urutan tampilan live result mengikuti jadwal rundown. Bracket yang sudah dipakai kategori lain tidak ditampilkan.') }}
        </p>
        @error('bracket_id')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
    </div>

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
            <button
                type="button"
                id="fetch-sheets-btn"
                data-fetch-sheets
                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-zinc-200 bg-white text-zinc-800 shadow-xs hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white dark:hover:bg-zinc-600/75"
                aria-label="{{ __('Fetch Sheets') }}"
            >
                <flux:icon name="arrow-path" variant="mini" class="size-5" />
            </button>
        </div>
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
            {{ __('Contoh: dari URL') }} <code class="rounded bg-zinc-100 px-1 dark:bg-zinc-700">https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit</code>
        </p>
        <p id="fetch-sheets-status" class="mt-1 hidden text-sm" role="status"></p>
        @error('spreadsheet_id')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div id="fetch-loading" class="hidden flex items-center gap-2 text-sm text-blue-600 dark:text-blue-400">
        <flux:icon name="arrow-path" class="size-4 animate-spin" />
        <span>{{ __('Mengambil daftar sheet...') }}</span>
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
