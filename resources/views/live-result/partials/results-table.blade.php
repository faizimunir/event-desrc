@php
    $poinMotoColumns = collect([
        ['flag' => 'has_poin_moto_1', 'key' => 'poin_moto_1', 'label' => 'Poin Moto 1'],
        ['flag' => 'has_poin_moto_2', 'key' => 'poin_moto_2', 'label' => 'Poin Moto 2'],
        ['flag' => 'has_poin_moto_3', 'key' => 'poin_moto_3', 'label' => 'Poin Moto 3'],
    ])->filter(fn ($col) => $sheetData['columns'][$col['flag']] ?? false);
@endphp

@foreach($sheetData['groups'] as $groupIndex => $group)
    <section class="live-result-group" wire:key="live-result-group-{{ $groupIndex }}">
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
                    @if(! empty($group['data']))
                        @foreach($group['data'] as $row)
                            <tr>
                                <td class="cell-plate whitespace-nowrap">{{ $row['plate'] ?? '-' }}</td>
                                <td>
                                    <div class="min-w-[120px] space-y-0.5">
                                        @if(! empty($row['nama']))<div class="font-bold text-zinc-900 dark:text-white">{{ $row['nama'] }}</div>@endif
                                        @if(! empty($row['panggilan']))<div class="text-xs text-zinc-600 dark:text-zinc-400">{{ $row['panggilan'] }}</div>@endif
                                        @if(! empty($row['team']))<div class="text-xs italic text-zinc-500 dark:text-zinc-400">{{ $row['team'] }}</div>@endif
                                    </div>
                                </td>
                                <td class="cell-gate whitespace-nowrap">
                                    @php
                                        $gates = array_filter([$row['gate_moto_1'] ?? '', $row['gate_moto_2'] ?? '', $row['gate_moto_3'] ?? '']);
                                    @endphp
                                    @if(! empty($gates))
                                        {{ implode(' | ', $gates) }}
                                    @else
                                        {{ ! empty($row['gate']) ? $row['gate'] : '-' }}
                                    @endif
                                </td>
                                @foreach($poinMotoColumns as $poinColumn)
                                    <td class="cell-poin whitespace-nowrap">{{ ! empty($row[$poinColumn['key']]) ? $row[$poinColumn['key']] : '-' }}</td>
                                @endforeach
                                @if($sheetData['is_qualifikasi'] ?? false)
                                    <td class="cell-total whitespace-nowrap">{{ ! empty($row['total']) ? $row['total'] : '-' }}</td>
                                @endif
                                <td class="cell-rank whitespace-nowrap">{{ ! empty($row['rank']) ? $row['rank'] : '-' }}</td>
                                <td class="font-medium text-zinc-900 dark:text-white">{{ ! empty($row['ket']) ? $row['ket'] : '-' }}</td>
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
    </section>
@endforeach
@if(empty($sheetData['groups']))
    <div class="bento-empty-state !py-12">
        <p class="text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Tidak ada data yang ditemukan.') }}</p>
    </div>
@endif
