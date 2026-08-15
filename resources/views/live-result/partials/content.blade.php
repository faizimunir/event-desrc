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
                            wire:target="selectCategory,selectRound"
                            class="live-result-chip live-result-chip--block live-result-chip--round {{ $selectedRound == $round ? 'live-result-chip--round-active' : '' }}"
                        >
                            {{ $round }}
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
            @php
                $poinMotoColumns = collect([
                    ['flag' => 'has_poin_moto_1', 'key' => 'poin_moto_1', 'label' => 'Poin Moto 1'],
                    ['flag' => 'has_poin_moto_2', 'key' => 'poin_moto_2', 'label' => 'Poin Moto 2'],
                    ['flag' => 'has_poin_moto_3', 'key' => 'poin_moto_3', 'label' => 'Poin Moto 3'],
                ])->filter(fn ($col) => $sheetData['columns'][$col['flag']] ?? false);
            @endphp
            @foreach($sheetData['groups'] as $groupIndex => $group)
                <div class="mb-8 {{ $groupIndex > 0 ? 'mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-700' : '' }}">
                    <h3 class="live-result-group-title mb-4">{{ $group['name'] }}</h3>
                    @php
                        $isFinal = stripos($selectedRound ?? '', 'final') !== false;
                        $showKeterangan = !empty($sheetData['keterangan']) && (!$isFinal || $groupIndex === 0);
                    @endphp
                    @if($showKeterangan)
                        <div class="live-result-keterangan mb-5">
                            <flux:icon name="info" class="live-result-keterangan__icon" />
                            <span>{{ $sheetData['keterangan'] }}</span>
                        </div>
                    @endif

                    <div class="live-result-table-wrap">
                        <table class="live-result-table">
                            <thead>
                                <tr>
                                    <th>Plate</th>
                                    <th>Riders</th>
                                    <th class="text-center">Gate Moto</th>
                                    @foreach($poinMotoColumns as $poinColumn)
                                        <th class="text-center">{{ $poinColumn['label'] }}</th>
                                    @endforeach
                                    @if($sheetData['is_qualifikasi'] ?? false)
                                        <th class="text-center">Total</th>
                                    @endif
                                    <th class="text-center">Rank</th>
                                    <th>Ket</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(!empty($group['data']))
                                    @foreach($group['data'] as $row)
                                        <tr>
                                            <td class="cell-plate whitespace-nowrap">{{ $row['plate'] ?? '-' }}</td>
                                            <td>
                                                <div class="min-w-[120px] space-y-0.5">
                                                    @if(!empty($row['nama']))<div class="font-bold text-zinc-900 dark:text-white">{{ $row['nama'] }}</div>@endif
                                                    @if(!empty($row['panggilan']))<div class="text-xs text-zinc-600 dark:text-zinc-400">{{ $row['panggilan'] }}</div>@endif
                                                    @if(!empty($row['team']))<div class="text-xs italic text-zinc-500 dark:text-zinc-400">{{ $row['team'] }}</div>@endif
                                                </div>
                                            </td>
                                            <td class="cell-gate whitespace-nowrap">
                                                @php
                                                    $gates = array_filter([$row['gate_moto_1'] ?? '', $row['gate_moto_2'] ?? '', $row['gate_moto_3'] ?? '']);
                                                @endphp
                                                @if(!empty($gates))
                                                    {{ implode(' | ', $gates) }}
                                                @else
                                                    {{ !empty($row['gate']) ? $row['gate'] : '-' }}
                                                @endif
                                            </td>
                                            @foreach($poinMotoColumns as $poinColumn)
                                                <td class="cell-poin whitespace-nowrap">{{ !empty($row[$poinColumn['key']]) ? $row[$poinColumn['key']] : '-' }}</td>
                                            @endforeach
                                            @if($sheetData['is_qualifikasi'] ?? false)
                                                <td class="cell-total whitespace-nowrap">{{ !empty($row['total']) ? $row['total'] : '-' }}</td>
                                            @endif
                                            <td class="cell-rank whitespace-nowrap">{{ !empty($row['rank']) ? $row['rank'] : '-' }}</td>
                                            <td class="font-medium text-zinc-900 dark:text-white">{{ !empty($row['ket']) ? $row['ket'] : '-' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="{{ $sheetData['column_count'] }}" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tidak ada data') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
            @if(empty($sheetData['groups']))
                <div class="bento-empty-state !py-12">
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Tidak ada data yang ditemukan.') }}</p>
                </div>
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
