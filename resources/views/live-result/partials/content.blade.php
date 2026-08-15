<div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-zinc-200/80 bg-gradient-to-br from-zinc-900 via-zinc-900 to-zinc-800 px-4 py-3 text-white dark:border-zinc-700">
    <div class="flex min-w-0 items-center gap-3">
        <span
            class="live-result-status-indicator"
            wire:loading.class="is-loading"
            wire:target="selectCategory,selectRound,tick"
            aria-hidden="true"
        >
            <span class="live-result-status-indicator__ping"></span>
            <span class="live-result-status-indicator__dot"></span>
            <span class="live-result-status-indicator__spinner"></span>
        </span>
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">
                <span wire:loading.remove.delay.shortest wire:target="selectCategory,selectRound">{{ __('Live now') }}</span>
                <span wire:loading.delay.shortest wire:target="selectCategory,selectRound">{{ __('Memuat data...') }}</span>
            </p>
            <p class="font-mono text-xl font-semibold tabular-nums tracking-tight" x-text="clock()"></p>
        </div>
    </div>

    @if (! empty($monitorSummary))
        <div class="flex flex-wrap gap-1.5">
            @if (($monitorSummary['live'] ?? 0) > 0)
                <span class="inline-flex items-center gap-1 rounded-full bg-green-500/20 px-2 py-0.5 text-[10px] font-semibold text-green-300">
                    <span class="size-1.5 animate-pulse rounded-full bg-green-400"></span>
                    {{ __('Live') }} {{ $monitorSummary['live'] }}
                </span>
            @endif
            @if (($monitorSummary['due'] ?? 0) > 0)
                <span class="rounded-full bg-orange-500/20 px-2 py-0.5 text-[10px] font-semibold text-orange-300">
                    {{ __('Due') }} {{ $monitorSummary['due'] }}
                </span>
            @endif
            @if (($monitorSummary['overdue'] ?? 0) > 0)
                <span class="rounded-full bg-red-500/20 px-2 py-0.5 text-[10px] font-semibold text-red-300">
                    {{ __('Overdue') }} {{ $monitorSummary['overdue'] }}
                </span>
            @endif
            @if (($monitorSummary['delayed'] ?? 0) > 0)
                <span class="rounded-full bg-amber-500/20 px-2 py-0.5 text-[10px] font-semibold text-amber-300">
                    {{ __('Delayed') }} {{ $monitorSummary['delayed'] }}
                </span>
            @endif
        </div>
    @endif
</div>

@if($categories->count() > 0)
    <div class="mb-6">
        <span class="live-result-filter-label">{{ __('Pilih Kategori') }}</span>
        <div class="space-y-3">
            @foreach($categoryGroups as $group)
                @php
                    /** @var \App\Models\Rundown|null $rundown */
                    $rundown = $group['rundown'] ?? null;
                    $appearance = $rundown?->monitorAppearance($now ?? now()) ?? [
                        'card' => 'border-zinc-200/80 bg-white dark:border-zinc-700/80 dark:bg-zinc-900/30',
                        'badge' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300',
                        'accent' => 'text-zinc-700 dark:text-zinc-200',
                        'pulse' => false,
                    ];
                    $chipCount = $group['categories']->count();
                    $gridClass = $chipCount === 1
                        ? 'grid-cols-1 sm:grid-cols-2'
                        : ($chipCount === 2 ? 'grid-cols-1 sm:grid-cols-2' : 'grid-cols-1 sm:grid-cols-2');
                @endphp
                <section class="overflow-hidden rounded-2xl border {{ $appearance['card'] }}">
                    @if ($group['header'])
                        <header class="flex flex-wrap items-center justify-between gap-2 border-b border-black/5 px-3 py-2.5 dark:border-white/5 sm:px-4">
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($rundown)
                                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $appearance['badge'] }}">
                                            @if ($appearance['pulse'])
                                                <span class="size-1.5 animate-pulse rounded-full bg-current"></span>
                                            @endif
                                            {{ $rundown->monitorStatusLabel($now ?? now()) }}
                                        </span>
                                    @endif
                                    <h2 class="text-sm font-semibold uppercase tracking-wide {{ $appearance['accent'] }}">
                                        {{ $group['header'] }}
                                    </h2>
                                </div>
                                @if ($rundown?->formattedActualTimeRange())
                                    <p class="mt-1 text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400">
                                        {{ __('Actual') }}: {{ $rundown->formattedActualTimeRange() }}
                                    </p>
                                @endif
                            </div>
                            <span class="shrink-0 rounded-full bg-black/5 px-2 py-0.5 text-[10px] font-semibold text-zinc-500 dark:bg-white/10 dark:text-zinc-300">
                                {{ $chipCount }} {{ __('kategori') }}
                            </span>
                        </header>
                    @endif

                    <div class="grid gap-2 p-3 sm:gap-2.5 sm:p-4 {{ $gridClass }}">
                        @foreach($group['categories'] as $category)
                            @php
                                $isActive = $selectedCategory && $selectedCategory->id == $category->id;
                            @endphp
                            <button
                                type="button"
                                wire:click="selectCategory({{ $category->id }})"
                                wire:loading.attr="disabled"
                                wire:target="selectCategory,selectRound"
                                class="live-result-chip live-result-chip--block {{ $isActive ? 'live-result-chip--active' : '' }}"
                            >
                                <span class="line-clamp-2">{{ $category->title }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    @if($selectedCategory)
        @if($selectedCategory->selected_sheets && count($selectedCategory->selected_sheets) > 0)
            <div id="live-result-rounds" class="live-result-scroll-target mb-6">
                <span class="live-result-filter-label">{{ __('Pilih Round') }}</span>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                    @foreach($selectedCategory->selected_sheets as $round)
                        <button
                            type="button"
                            wire:click="selectRound(@js($round))"
                            wire:loading.attr="disabled"
                            wire:target="selectRound(@js($round))"
                            class="live-result-chip live-result-chip--block live-result-chip--round relative {{ $selectedRound == $round ? 'live-result-chip--round-active' : '' }}"
                        >
                            <span
                                wire:loading.remove
                                wire:target="selectRound(@js($round))"
                                class="line-clamp-2"
                            >{{ $round }}</span>
                            <span
                                wire:loading.flex
                                wire:target="selectRound(@js($round))"
                                class="hidden items-center justify-center gap-2"
                            >
                                <flux:icon name="arrow-path" variant="mini" class="size-4 shrink-0 animate-spin" />
                                <span>{{ __('Memuat...') }}</span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        <div id="live-result-results" class="live-result-scroll-target">
        @if($selectedRound)
            <div class="live-result-context-bar mb-6">
                <flux:icon name="chart-bar" class="size-4 shrink-0 text-orange-500 dark:text-orange-400" />
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $selectedCategory->title }}</span>
                <span class="text-zinc-300 dark:text-zinc-600">·</span>
                <span class="text-sm font-semibold text-orange-600 dark:text-orange-400">{{ $selectedRound }}</span>
            </div>
        @endif

        @if($selectedRound && $sheetData)
            @if ($event->usesLiveResultCards())
                @include('live-result.partials.results-cards', [
                    'sheetData' => $sheetData,
                    'selectedRound' => $selectedRound,
                ])
            @else
                @include('live-result.partials.results-table', [
                    'sheetData' => $sheetData,
                    'selectedRound' => $selectedRound,
                ])
            @endif
        @elseif($selectedRound)
            <div class="bento-empty-state !py-12">
                <flux:icon name="exclamation-triangle" class="mx-auto size-10 text-amber-500" />
                <p class="mt-3 text-sm font-medium text-zinc-900 dark:text-white">{{ __('Gagal Memuat Data') }}</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tidak dapat mengambil data dari Google Sheets. Silakan coba lagi nanti.') }}</p>
            </div>
        @elseif (! $selectedRound)
            <div class="bento-empty-state !py-12">
                <flux:icon name="radio" class="mx-auto size-10 text-zinc-400 dark:text-zinc-500" />
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Silakan pilih round untuk menampilkan data.') }}</p>
            </div>
        @endif
        </div>
    @else
        <div class="bento-empty-state !py-12">
            <flux:icon name="radio" class="mx-auto size-10 text-zinc-400 dark:text-zinc-500" />
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Silakan pilih kategori untuk melihat hasil live.') }}</p>
        </div>
    @endif
@else
    <div class="bento-empty-state !py-12">
        <flux:icon name="chart-bar" class="mx-auto size-10 text-zinc-400 dark:text-zinc-500" />
        <p class="mt-3 text-sm font-medium text-zinc-900 dark:text-white">{{ __('Belum Ada Kategori') }}</p>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada kategori live result yang tersedia untuk event ini.') }}</p>
    </div>
@endif
