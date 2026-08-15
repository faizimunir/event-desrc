@php
    $poinMotoColumns = collect([
        ['flag' => 'has_poin_moto_1', 'key' => 'poin_moto_1', 'label' => 'Poin Moto 1'],
        ['flag' => 'has_poin_moto_2', 'key' => 'poin_moto_2', 'label' => 'Poin Moto 2'],
        ['flag' => 'has_poin_moto_3', 'key' => 'poin_moto_3', 'label' => 'Poin Moto 3'],
    ])->filter(fn ($col) => $sheetData['columns'][$col['flag']] ?? false);
    $showTotal = $sheetData['is_qualifikasi'] ?? false;
@endphp

@foreach($sheetData['groups'] as $groupIndex => $group)
    <section class="live-result-group" wire:key="live-result-group-card-{{ $groupIndex }}">
        <div class="live-result-group-sticky">
            <h3 class="live-result-group-title">{{ $group['name'] }}</h3>
        </div>
        @php
            $isFinal = stripos($selectedRound ?? '', 'final') !== false;
            $showKeterangan = ! empty($sheetData['keterangan']) && (! $isFinal || $groupIndex === 0);
        @endphp
        @if($showKeterangan)
            <div class="live-result-keterangan mb-5">
                <flux:icon name="info" class="live-result-keterangan__icon" />
                <span>{{ $sheetData['keterangan'] }}</span>
            </div>
        @endif

        @if(! empty($group['data']))
            <div class="live-result-card-list">
                @foreach($group['data'] as $row)
                    @php
                        $gates = array_filter([$row['gate_moto_1'] ?? '', $row['gate_moto_2'] ?? '', $row['gate_moto_3'] ?? '']);
                        $gateDisplay = ! empty($gates)
                            ? implode(' | ', $gates)
                            : (! empty($row['gate']) ? $row['gate'] : '-');
                        $rank = ! empty($row['rank']) ? $row['rank'] : null;
                        $total = ! empty($row['total']) ? $row['total'] : null;
                        $ket = ! empty($row['ket']) ? $row['ket'] : null;
                    @endphp
                    <article class="live-result-card">
                        <div class="live-result-card__top">
                            <div class="live-result-card__plate" aria-label="{{ __('Plate') }}">
                                {{ $row['plate'] ?? '-' }}
                            </div>
                            <div class="live-result-card__rider min-w-0 flex-1">
                                @if(! empty($row['nama']))
                                    <p class="live-result-card__name">{{ $row['nama'] }}</p>
                                @endif
                                @if(! empty($row['panggilan']))
                                    <p class="live-result-card__nick">{{ $row['panggilan'] }}</p>
                                @endif
                                @if(! empty($row['team']))
                                    <p class="live-result-card__team">{{ $row['team'] }}</p>
                                @endif
                            </div>
                            <div class="live-result-card__rank-block" aria-label="{{ __('Rank') }}">
                                <span class="live-result-card__meta-label">{{ __('Rank') }}</span>
                                <span class="live-result-card__rank">{{ $rank ?? '-' }}</span>
                            </div>
                        </div>

                        <div class="live-result-card__stats">
                            <div class="live-result-card__stat">
                                <span class="live-result-card__meta-label">{{ __('Gate Moto') }}</span>
                                <span class="live-result-card__stat-value">{{ $gateDisplay }}</span>
                            </div>
                            @foreach($poinMotoColumns as $poinColumn)
                                <div class="live-result-card__stat">
                                    <span class="live-result-card__meta-label">{{ $poinColumn['label'] }}</span>
                                    <span class="live-result-card__stat-value">
                                        {{ ! empty($row[$poinColumn['key']]) ? $row[$poinColumn['key']] : '-' }}
                                    </span>
                                </div>
                            @endforeach
                            @if($showTotal)
                                <div class="live-result-card__stat live-result-card__stat--accent">
                                    <span class="live-result-card__meta-label">{{ __('Total') }}</span>
                                    <span class="live-result-card__stat-value">{{ $total ?? '-' }}</span>
                                </div>
                            @endif
                        </div>

                        @if($ket)
                            <div class="live-result-card__ket">
                                <span class="live-result-card__meta-label">{{ __('Ket') }}</span>
                                <span>{{ $ket }}</span>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <div class="bento-empty-state !py-8">
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Tidak ada data') }}</p>
            </div>
        @endif
    </section>
@endforeach
@if(empty($sheetData['groups']))
    <div class="bento-empty-state !py-12">
        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Tidak ada data yang ditemukan.') }}</p>
    </div>
@endif
