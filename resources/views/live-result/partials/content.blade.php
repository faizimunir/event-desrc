@if($categories->count() > 0)
    <div class="mb-6">
        <flux:label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Pilih Kategori:') }}</flux:label>
        <div class="flex flex-wrap gap-2">
            @foreach($categories as $category)
                <a
                    href="{{ route('live-result.show', ['event' => $event->slug, 'category' => $category->id]) }}"
                    wire:navigate
                    class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-colors {{ $selectedCategory && $selectedCategory->id == $category->id ? 'bg-orange-500 text-white hover:bg-orange-600' : 'bg-zinc-200 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600' }}"
                >
                    {{ $category->title }}
                </a>
            @endforeach
        </div>
    </div>

    @if($selectedCategory)
        @if($selectedCategory->selected_sheets && count($selectedCategory->selected_sheets) > 0)
            <div class="mb-6">
                <flux:label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Pilih Round:') }}</flux:label>
                <div class="flex flex-wrap gap-2">
                    @foreach($selectedCategory->selected_sheets as $round)
                        <a
                            href="{{ route('live-result.show', ['event' => $event->slug, 'category' => $selectedCategory->id, 'round' => $round]) }}"
                            wire:navigate
                            class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium transition-colors {{ $selectedRound == $round ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-zinc-200 text-zinc-700 hover:bg-zinc-300 dark:bg-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-600' }}"
                        >
                            {{ $round }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if($selectedRound && $sheetData && is_array($sheetData) && isset($sheetData['groups']))
            @foreach($sheetData['groups'] as $groupIndex => $group)
                <div class="mb-8 {{ $groupIndex > 0 ? 'mt-8 pt-8 border-t-2 border-zinc-300 dark:border-zinc-600' : '' }}">
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-3">{{ $group['name'] }}</h3>
                    @php
                        $isFinal = stripos($selectedRound ?? '', 'final') !== false;
                        $showKeterangan = !empty($sheetData['keterangan']) && (!$isFinal || $groupIndex === 0);
                    @endphp
                    @if($showKeterangan)
                        <div class="my-5 mb-6 px-4 py-3 bg-blue-50 dark:bg-orange-500/20 border-l-4 border-blue-500 dark:border-orange-500 rounded-r-lg text-sm text-blue-900 dark:text-orange-100">
                            {{ $sheetData['keterangan'] }}
                        </div>
                    @endif

                    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-r border-zinc-200 dark:border-zinc-700">Plate</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-r border-zinc-200 dark:border-zinc-700">Riders</th>
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-r border-zinc-200 dark:border-zinc-700">Gate Moto</th>
                                    @if($sheetData['columns']['has_poin_moto_1'] ?? false)
                                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-r border-zinc-200 dark:border-zinc-700">Poin Moto 1</th>
                                    @endif
                                    @if($sheetData['columns']['has_poin_moto_2'] ?? false)
                                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-r border-zinc-200 dark:border-zinc-700">Poin Moto 2</th>
                                    @endif
                                    @if($sheetData['columns']['has_poin_moto_3'] ?? false)
                                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-r border-zinc-200 dark:border-zinc-700">Poin Moto 3</th>
                                    @endif
                                    @if($sheetData['is_qualifikasi'] ?? false)
                                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-r border-zinc-200 dark:border-zinc-700">Total</th>
                                    @endif
                                    <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-r border-zinc-200 dark:border-zinc-700">Rank</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Ket</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                                @if(!empty($group['data']))
                                    @foreach($group['data'] as $row)
                                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-zinc-900 dark:text-white border-r border-zinc-200 dark:border-zinc-700">{{ $row['plate'] ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm border-r border-zinc-200 dark:border-zinc-700">
                                                <div class="space-y-0.5 min-w-[120px]">
                                                    @if(!empty($row['nama']))<div class="font-bold text-zinc-900 dark:text-white">{{ $row['nama'] }}</div>@endif
                                                    @if(!empty($row['panggilan']))<div class="text-zinc-600 dark:text-zinc-400 text-xs">{{ $row['panggilan'] }}</div>@endif
                                                    @if(!empty($row['team']))<div class="text-xs italic text-zinc-500 dark:text-zinc-400">{{ $row['team'] }}</div>@endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-medium text-zinc-900 dark:text-white border-r border-zinc-200 dark:border-zinc-700">
                                                @php
                                                    $gates = array_filter([$row['gate_moto_1'] ?? '', $row['gate_moto_2'] ?? '', $row['gate_moto_3'] ?? '']);
                                                @endphp
                                                @if(!empty($gates))
                                                    {{ implode(' | ', $gates) }}
                                                @else
                                                    {{ !empty($row['gate']) ? $row['gate'] : '-' }}
                                                @endif
                                            </td>
                                            @if($sheetData['columns']['has_poin_moto_1'] ?? false)
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-zinc-900 dark:text-white border-r border-zinc-200 dark:border-zinc-700">{{ !empty($row['poin_moto_1']) ? $row['poin_moto_1'] : '-' }}</td>
                                            @endif
                                            @if($sheetData['columns']['has_poin_moto_2'] ?? false)
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-zinc-900 dark:text-white border-r border-zinc-200 dark:border-zinc-700">{{ !empty($row['poin_moto_2']) ? $row['poin_moto_2'] : '-' }}</td>
                                            @endif
                                            @if($sheetData['columns']['has_poin_moto_3'] ?? false)
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-zinc-900 dark:text-white border-r border-zinc-200 dark:border-zinc-700">{{ !empty($row['poin_moto_3']) ? $row['poin_moto_3'] : '-' }}</td>
                                            @endif
                                            @if($sheetData['is_qualifikasi'] ?? false)
                                                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-center text-zinc-900 dark:text-white border-r border-zinc-200 dark:border-zinc-700">{{ !empty($row['total']) ? $row['total'] : '-' }}</td>
                                            @endif
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-center text-zinc-900 dark:text-white border-r border-zinc-200 dark:border-zinc-700">{{ !empty($row['rank']) ? $row['rank'] : '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-zinc-900 dark:text-white">{{ !empty($row['ket']) ? $row['ket'] : '-' }}</td>
                                        </tr>
                                    @endforeach
                                @else
                                    @php
                                        $colspan = 3 + ($sheetData['columns']['has_poin_moto_1'] ?? false ? 1 : 0) + ($sheetData['columns']['has_poin_moto_2'] ?? false ? 1 : 0) + ($sheetData['columns']['has_poin_moto_3'] ?? false ? 1 : 0) + ($sheetData['is_qualifikasi'] ?? false ? 1 : 0) + 2;
                                    @endphp
                                    <tr>
                                        <td colspan="{{ $colspan }}" class="px-4 py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tidak ada data') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
            @if(empty($sheetData['groups']))
                <p class="text-center py-8 text-zinc-500 dark:text-zinc-400">{{ __('Tidak ada data yang ditemukan.') }}</p>
            @endif
        @elseif($selectedRound)
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-8 text-center">
                <flux:icon name="exclamation-triangle" class="mx-auto size-12 text-amber-500" />
                <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">{{ __('Gagal Memuat Data') }}</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Tidak dapat mengambil data dari Google Sheets. Silakan coba lagi nanti.') }}</p>
            </div>
        @else
            <p class="text-center py-8 text-zinc-500 dark:text-zinc-400">{{ __('Silakan pilih round untuk menampilkan data.') }}</p>
        @endif
    @else
        <p class="text-center py-8 text-zinc-500 dark:text-zinc-400">{{ __('Silakan pilih kategori untuk melihat hasil live.') }}</p>
    @endif
@else
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-8 text-center">
        <flux:icon name="chart-bar" class="mx-auto size-12 text-zinc-400 dark:text-zinc-500" />
        <h3 class="mt-2 text-sm font-medium text-zinc-900 dark:text-white">{{ __('Belum Ada Kategori') }}</h3>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Belum ada kategori live result yang tersedia untuk event ini.') }}</p>
    </div>
@endif
