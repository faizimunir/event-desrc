<div class="live-result-status-bar mb-6" role="status" aria-live="polite">
    <span
        class="live-result-status-indicator"
        wire:loading.class="is-loading"
        wire:target="selectCategory,selectRound"
        aria-hidden="true"
    >
        <span class="live-result-status-indicator__ping"></span>
        <span class="live-result-status-indicator__dot"></span>
        <span class="live-result-status-indicator__spinner"></span>
    </span>
    <span class="live-result-status-bar__label">
        <span wire:loading.remove.delay.shortest wire:target="selectCategory,selectRound">{{ __('Auto refresh') }}</span>
        <span wire:loading.delay.shortest wire:target="selectCategory,selectRound">{{ __('Memuat data...') }}</span>
    </span>
</div>

@if($categories->count() > 0)
    <div class="mb-6">
        <span class="live-result-filter-label">{{ __('Pilih Kategori') }}</span>
        <div class="space-y-4">
            @foreach($categoryGroups as $group)
                <div>
                    @if ($group['header'])
                        <div class="mb-2 flex items-center gap-3">
                            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
                            <p class="shrink-0 text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                {{ $group['header'] }}
                            </p>
                            <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
                        </div>
                    @endif
                    <div class="live-result-filter-grid">
                        @foreach($group['categories'] as $category)
                            <button
                                type="button"
                                wire:click="selectCategory({{ $category->id }})"
                                wire:loading.attr="disabled"
                                wire:target="selectCategory,selectRound"
                                class="live-result-chip {{ $selectedCategory && $selectedCategory->id == $category->id ? 'live-result-chip--active' : '' }}"
                            >
                                {{ $category->title }}
                            </button>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @if($selectedCategory)
        @if($selectedCategory->selected_sheets && count($selectedCategory->selected_sheets) > 0)
            <div class="mb-6">
                <span class="live-result-filter-label">{{ __('Pilih Round') }}</span>
                <div class="live-result-filter-grid live-result-filter-grid--rounds">
                    @foreach($selectedCategory->selected_sheets as $round)
                        <button
                            type="button"
                            wire:click="selectRound(@js($round))"
                            wire:loading.attr="disabled"
                            wire:target="selectCategory,selectRound"
                            class="live-result-chip {{ $selectedRound == $round ? 'live-result-chip--round-active' : '' }}"
                        >
                            {{ $round }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

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
        @else
            <div class="bento-empty-state !py-12">
                <flux:icon name="radio" class="mx-auto size-10 text-zinc-400 dark:text-zinc-500" />
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Silakan pilih round untuk menampilkan data.') }}</p>
            </div>
        @endif
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
