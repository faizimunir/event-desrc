<div
    wire:poll.5s="refreshBoard"
    x-data="{
        nowMs: Date.parse(@js($clockIso)),
        tick() { this.nowMs = Date.now() },
        clock() {
            const d = new Date(this.nowMs)
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false })
        },
        elapsed(iso) {
            if (!iso) return '—'
            const start = Date.parse(iso)
            if (Number.isNaN(start)) return '—'
            let sec = Math.max(0, Math.floor((this.nowMs - start) / 1000))
            const h = Math.floor(sec / 3600)
            sec %= 3600
            const m = Math.floor(sec / 60)
            const s = sec % 60
            return h > 0
                ? `${h}:${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
                : `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`
        }
    }"
    x-init="setInterval(() => tick(), 1000)"
>
    @if (! $event->has_live_result)
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('Live Result') }}
            </h2>
            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                0
            </span>
        </div>

        <div class="users-list-panel px-4 py-12 text-center">
            <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="chart-bar" class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">
                {{ __('Fitur Live Result belum diaktifkan.') }}
            </p>
            <p class="mx-auto mt-1 max-w-md text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Aktifkan terlebih dahulu di halaman Edit Event untuk mengelola kategori dan menampilkan di halaman publik.') }}
            </p>
            @canAs('event.update')
                <div class="mt-4">
                    <flux:button variant="primary" size="sm" :href="route('events.edit', $event)" wire:navigate>
                        {{ __('Edit Event') }}
                    </flux:button>
                </div>
            @endcanAs
        </div>
    @else
        <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
            @canAs('manage_live_results')
                <flux:button
                    variant="primary"
                    :href="route('events.live-result-categories.create', $event)"
                    wire:navigate
                    icon="plus"
                    square
                    class="shrink-0"
                    :aria-label="__('Tambah Kategori')"
                />
            @endcanAs

            <flux:input
                wire:model.live.debounce.500ms="search"
                type="search"
                :placeholder="__('Search by name…')"
                class="min-w-0 flex-1"
            />

            @if ($this->categoryTotal > 0)
                @canAs('manage_live_results')
                    <flux:button
                        type="button"
                        variant="outline"
                        square
                        class="shrink-0"
                        wire:click="syncAll"
                        wire:loading.attr="disabled"
                        wire:target="syncAll"
                        :aria-label="__('Sync All')"
                    >
                        <span wire:loading.remove wire:target="syncAll" class="inline-flex">
                            <flux:icon :name="$justSyncedAll ? 'check' : 'arrow-path'" variant="mini" />
                        </span>
                        <span wire:loading wire:target="syncAll" class="inline-flex">
                            <flux:icon name="arrow-path" variant="mini" class="animate-spin" />
                        </span>
                    </flux:button>
                @endcanAs

                <flux:button
                    variant="outline"
                    :href="route('print-center.index')"
                    wire:navigate
                    icon="printer"
                    square
                    class="shrink-0"
                    :aria-label="__('Print Center')"
                />
            @endif
        </div>

        {{-- Realtime monitor bar --}}
        <div class="mb-4 overflow-hidden rounded-2xl border border-zinc-200 bg-gradient-to-br from-zinc-900 via-zinc-900 to-zinc-800 p-4 text-white shadow-sm dark:border-zinc-700">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="relative flex size-2.5">
                            <span class="absolute inline-flex size-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex size-2.5 rounded-full bg-green-400"></span>
                        </span>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">
                            {{ __('Rundown Monitor') }}
                        </p>
                    </div>
                    <p class="mt-1 font-mono text-2xl font-semibold tabular-nums tracking-tight" x-text="clock()"></p>
                    <p class="mt-0.5 text-xs text-zinc-400">
                        {{ __('Auto-refresh every 5s') }} · {{ number_format($this->categoryTotal) }} {{ __('categories') }}
                    </p>
                </div>

                @php $summary = $this->monitorSummary; @endphp
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-500/20 px-2.5 py-1 text-[11px] font-semibold text-green-300">
                        <span class="size-1.5 rounded-full bg-green-400"></span>
                        {{ __('Live') }} {{ $summary['live'] }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-orange-500/20 px-2.5 py-1 text-[11px] font-semibold text-orange-300">
                        {{ __('Due') }} {{ $summary['due'] }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/20 px-2.5 py-1 text-[11px] font-semibold text-red-300">
                        {{ __('Overdue') }} {{ $summary['overdue'] }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/20 px-2.5 py-1 text-[11px] font-semibold text-amber-300">
                        {{ __('Delayed') }} {{ $summary['delayed'] }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-500/20 px-2.5 py-1 text-[11px] font-semibold text-sky-300">
                        {{ __('Ontime') }} {{ $summary['ontime'] }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-500/20 px-2.5 py-1 text-[11px] font-semibold text-zinc-300">
                        {{ __('Upcoming') }} {{ $summary['upcoming'] }}
                    </span>
                </div>
            </div>
        </div>

        @if ($this->categoryGroups->isEmpty())
            <div class="users-list-panel px-4 py-12 text-center">
                <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="chart-bar" class="size-5 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada kategori.') }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or add a new category.') }}</p>
            </div>
        @else
            <div class="space-y-4" wire:key="live-result-groups">
                @foreach ($this->categoryGroups as $group)
                    @php
                        /** @var \App\Models\Rundown|null $rundown */
                        $rundown = $group['rundown'] ?? null;
                        $appearance = $rundown?->monitorAppearance($now) ?? [
                            'card' => 'border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900/40',
                            'badge' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
                            'accent' => 'text-zinc-600 dark:text-zinc-300',
                            'pulse' => false,
                        ];
                        $monitorStatus = $rundown?->monitorStatus($now);
                        $actualRange = $rundown?->formattedActualTimeRange();
                        $startDelay = $rundown?->startDelayMinutes();
                        $endDelay = $rundown?->endDelayMinutes();
                    @endphp
                    <div
                        wire:key="live-result-group-{{ $group['key'] }}"
                        class="overflow-hidden rounded-2xl border {{ $appearance['card'] }}"
                    >
                        @if ($group['header'])
                            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-black/5 px-4 py-3 dark:border-white/5">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if ($rundown)
                                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide {{ $appearance['badge'] }}">
                                                @if ($appearance['pulse'])
                                                    <span class="relative flex size-1.5">
                                                        <span class="absolute inline-flex size-full animate-ping rounded-full bg-current opacity-60"></span>
                                                        <span class="relative inline-flex size-1.5 rounded-full bg-current"></span>
                                                    </span>
                                                @endif
                                                {{ $rundown->monitorStatusLabel($now) }}
                                            </span>
                                        @endif
                                        <h3 class="text-sm font-semibold uppercase tracking-wide {{ $appearance['accent'] }}">
                                            {{ $group['header'] }}
                                        </h3>
                                    </div>

                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs tabular-nums text-zinc-600 dark:text-zinc-300">
                                        @if ($rundown)
                                            <span>
                                                <span class="text-zinc-400">{{ __('Schedule') }}:</span>
                                                {{ $rundown->formattedTimeRange() }}
                                            </span>
                                        @endif
                                        @if ($actualRange)
                                            <span>
                                                <span class="text-zinc-400">{{ __('Actual') }}:</span>
                                                {{ $actualRange }}
                                            </span>
                                        @endif
                                        @if ($rundown?->isPlaying() && $rundown->actual_started_at)
                                            <span class="font-semibold {{ $appearance['accent'] }}">
                                                <span class="font-normal text-zinc-400">{{ __('Elapsed') }}:</span>
                                                <span x-text="elapsed(@js($rundown->actual_started_at->toIso8601String()))"></span>
                                            </span>
                                        @elseif ($rundown?->isCompleted() && $rundown->formattedElapsed())
                                            <span>
                                                <span class="text-zinc-400">{{ __('Duration') }}:</span>
                                                {{ $rundown->formattedElapsed() }}
                                            </span>
                                        @endif
                                        @if ($startDelay !== null && $startDelay > 0)
                                            <span class="font-medium text-amber-700 dark:text-amber-300">
                                                {{ __('Start + :min m', ['min' => $startDelay]) }}
                                            </span>
                                        @endif
                                        @if ($endDelay !== null && $endDelay > 0)
                                            <span class="font-medium text-amber-700 dark:text-amber-300">
                                                {{ __('End + :min m', ['min' => $endDelay]) }}
                                            </span>
                                        @endif
                                        @if ($monitorStatus === 'upcoming' && $rundown)
                                            @php $until = $rundown->secondsUntilStart($now); @endphp
                                            @if ($until !== null && $until > 0)
                                                <span class="text-zinc-500">
                                                    {{ __('Starts in :min m', ['min' => (int) ceil($until / 60)]) }}
                                                </span>
                                            @endif
                                        @endif
                                    </div>
                                </div>

                                @if ($rundown)
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        @canAs('manage_live_results')
                                            @can('update', $event)
                                                @if ($rundown->isPlaying())
                                                    <flux:button
                                                        type="button"
                                                        variant="danger"
                                                        size="sm"
                                                        icon="stop"
                                                        wire:click="stopRundown({{ $rundown->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="stopRundown({{ $rundown->id }})"
                                                    >
                                                    </flux:button>
                                                @elseif ($rundown->isCompleted())
                                                    <flux:button
                                                        type="button"
                                                        variant="primary"
                                                        size="sm"
                                                        icon="play"
                                                        wire:click="playRundown({{ $rundown->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="playRundown({{ $rundown->id }})"
                                                        wire:confirm="{{ __('Start this rundown again? Previous actual times will be replaced.') }}"
                                                    >
                                                    </flux:button>
                                                @else
                                                    <flux:button
                                                        type="button"
                                                        variant="primary"
                                                        size="sm"
                                                        icon="play"
                                                        wire:click="playRundown({{ $rundown->id }})"
                                                        wire:loading.attr="disabled"
                                                        wire:target="playRundown({{ $rundown->id }})"
                                                    >
                                                    </flux:button>
                                                @endif
                                            @endcan
                                        @endcanAs
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div class="users-list-panel !rounded-none !border-0 !bg-transparent dark:!bg-transparent">
                            @forelse ($group['categories'] as $category)
                                @php
                                    $canUpdate = auth()->user()->canAs('manage_live_results') && auth()->user()->can('update', $event);
                                    $sheetCount = is_array($category->selected_sheets) ? count($category->selected_sheets) : 0;
                                    $metaParts = array_values(array_filter([
                                        $category->bracket?->name,
                                        $sheetCount > 0 ? $sheetCount.' '.__('sheets') : __('Belum dipilih'),
                                        $category->last_sync ? $category->last_sync->format('d M Y H:i') : __('Belum pernah'),
                                        \Illuminate\Support\Str::limit($category->spreadsheet_id, 22),
                                    ]));
                                @endphp

                                <div wire:key="live-result-category-{{ $category->id }}" class="users-list-row group !bg-transparent">
                                    @if ($canUpdate)
                                        <a
                                            href="{{ route('events.live-result-categories.edit', [$event, $category]) }}"
                                            wire:navigate
                                            class="flex min-w-0 flex-1 items-center gap-2.5"
                                        >
                                    @else
                                        <div class="flex min-w-0 flex-1 items-center gap-2.5">
                                    @endif
                                        <div class="users-list-avatar">
                                            <flux:icon name="chart-bar" variant="outline" class="size-4" />
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                                    {{ $category->title }}
                                                </p>
                                                @if ($category->is_active)
                                                    <span class="hidden shrink-0 rounded-full bg-green-500/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-green-600 sm:inline dark:bg-green-500/15 dark:text-green-400">
                                                        {{ __('Aktif') }}
                                                    </span>
                                                @else
                                                    <span class="hidden shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 sm:inline dark:bg-zinc-700 dark:text-zinc-400">
                                                        {{ __('Nonaktif') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ implode(' · ', $metaParts) }}
                                            </p>
                                        </div>
                                    @if ($canUpdate)
                                        </a>
                                    @else
                                        </div>
                                    @endif

                                    @if ($sheetCount > 0)
                                        @canAs('manage_live_results')
                                            <flux:button
                                                type="button"
                                                variant="ghost"
                                                square
                                                class="shrink-0 !text-green-600 hover:!text-green-700 dark:!text-green-400"
                                                wire:click="syncCategory({{ $category->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="syncCategory({{ $category->id }})"
                                                :aria-label="__('Sync')"
                                            >
                                                <span wire:loading.remove wire:target="syncCategory({{ $category->id }})" class="inline-flex">
                                                    <flux:icon :name="$justSyncedId === $category->id ? 'check' : 'arrow-path'" variant="mini" />
                                                </span>
                                                <span wire:loading wire:target="syncCategory({{ $category->id }})" class="inline-flex">
                                                    <flux:icon name="arrow-path" variant="mini" class="animate-spin" />
                                                </span>
                                            </flux:button>

                                            <flux:dropdown position="bottom" align="end">
                                                <flux:button
                                                    type="button"
                                                    variant="ghost"
                                                    icon="printer"
                                                    square
                                                    class="!text-orange-600 hover:!text-orange-700 dark:!text-orange-400"
                                                    :aria-label="__('Print')"
                                                />
                                                <flux:menu>
                                                    @foreach ($category->selected_sheets as $sheet)
                                                        <flux:menu.item
                                                            href="{{ route('events.live-result-categories.print', [$event, $category, 'round' => $sheet]) }}"
                                                            target="_blank"
                                                        >
                                                            {{ $sheet }}
                                                        </flux:menu.item>
                                                    @endforeach
                                                </flux:menu>
                                            </flux:dropdown>
                                        @endcanAs
                                    @endif

                                    @if ($canUpdate)
                                        <flux:icon
                                            name="chevron-right"
                                            variant="mini"
                                            class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                                        />
                                    @endif
                                </div>
                            @empty
                                <div class="px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Di luar lomba — tidak ada kategori live result.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</div>
