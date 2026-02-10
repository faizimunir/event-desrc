@if($categories->count() > 0)
    <!-- Category Filter -->
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Pilih Kategori:
        </label>
        <div class="flex flex-wrap gap-3">
            @foreach($categories as $category)
                <a 
                    href="/{{ $event->slug }}?category={{ $category->id }}"
                    class="category-link px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ $selectedCategory && $selectedCategory->id == $category->id ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}"
                >
                    {{ $category->title }}
                </a>
            @endforeach
        </div>
    </div>

    @if($selectedCategory)
        <!-- Round Filter -->
        @if($selectedCategory->selected_sheets && count($selectedCategory->selected_sheets) > 0)
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Pilih Round:
                </label>
                <div class="flex flex-wrap gap-3">
                    @foreach($selectedCategory->selected_sheets as $round)
                        <a 
                            href="/{{ $event->slug }}?category={{ $selectedCategory->id }}&round={{ urlencode($round) }}"
                            class="round-link px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200 {{ $selectedRound == $round ? 'bg-green-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}"
                        >
                            {{ $round }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Sheet Data Display -->
        @if($selectedRound && $sheetData && is_array($sheetData) && isset($sheetData['groups']))
            @foreach($sheetData['groups'] as $groupIndex => $group)
                <div class="mb-8 {{ $groupIndex > 0 ? 'mt-8 pt-8 border-t-2 border-gray-300 dark:border-gray-600' : '' }}">
                    <!-- Group Header -->
                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-3">{{ $group['name'] }}</h3>
                        
                        <!-- Keterangan Box (from B1) - Only show if not empty -->
                        @php
                            $isFinal = stripos($selectedRound ?? '', 'final') !== false;
                            $showKeterangan = !empty($sheetData['keterangan']) && (!$isFinal || $groupIndex === 0);
                        @endphp
                        @if($showKeterangan)
                            <div class="my-5 mb-6 px-3 py-2.5 sm:px-4 sm:py-3 bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500 dark:border-blue-400 rounded-r-md shadow-sm">
                                <div class="flex items-start gap-2">
                                    <!-- Info Icon -->
                                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                    </svg>
                                    <p class="text-xs sm:text-sm font-semibold text-blue-900 dark:text-blue-900 leading-relaxed flex-1">
                                        {{ $sheetData['keterangan'] }}
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto shadow-sm rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                        Plate
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                        Riders
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                        Gate Moto
                                    </th>
                                    @if($sheetData['columns']['has_poin_moto_1'])
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                            Poin Moto 1
                                        </th>
                                    @endif
                                    @if($sheetData['columns']['has_poin_moto_2'])
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                            Poin Moto 2
                                        </th>
                                    @endif
                                     @if($sheetData['columns']['has_poin_moto_3'])
                                         <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                             Poin Moto 3
                                         </th>
                                     @endif
                                     @if($sheetData['is_qualifikasi'] ?? false)
                                         <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                             Total
                                         </th>
                                     @endif
                                     <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-gray-700">
                                         Rank
                                     </th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Ket
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @if(!empty($group['data']))
                                    @foreach($group['data'] as $row)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <!-- Plate -->
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">
                                                {{ $row['plate'] ?? '-' }}
                                            </td>
                                            
                                            <!-- Riders (Nama, Panggilan, Team stacked) -->
                                            <td class="px-3 sm:px-4 py-3 sm:py-4 text-sm border-r border-gray-200 dark:border-gray-700">
                                                <div class="space-y-0.5 sm:space-y-1 min-w-[120px] sm:min-w-[150px]">
                                                    @if(!empty($row['nama']))
                                                        <div class="font-bold text-gray-900 dark:text-white leading-snug text-sm sm:text-base">
                                                            {{ $row['nama'] }}
                                                        </div>
                                                    @endif
                                                    @if(!empty($row['panggilan']))
                                                        <div class="text-gray-600 dark:text-gray-400 text-xs sm:text-sm leading-relaxed">
                                                            {{ $row['panggilan'] }}
                                                        </div>
                                                    @endif
                                                    @if(!empty($row['team']))
                                                        <div class="text-xs italic text-gray-500 dark:text-gray-400 leading-relaxed mt-0.5">
                                                            {{ $row['team'] }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            
                                            <!-- Gate Moto -->
                                            <td class="px-4 py-3 whitespace-nowrap text-sm text-center font-medium text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">
                                                @php
                                                    // Always combine gate motos if they exist (for all rounds)
                                                    $gates = [];
                                                    if (!empty($row['gate_moto_1'])) $gates[] = $row['gate_moto_1'];
                                                    if (!empty($row['gate_moto_2'])) $gates[] = $row['gate_moto_2'];
                                                    if (!empty($row['gate_moto_3'])) $gates[] = $row['gate_moto_3'];
                                                @endphp
                                                @if(!empty($gates))
                                                    <span class="inline-flex items-center gap-1">
                                                        @foreach($gates as $index => $gate)
                                                            <span>{{ $gate }}</span>
                                                            @if($index < count($gates) - 1)
                                                                <span class="text-gray-400">|</span>
                                                            @endif
                                                        @endforeach
                                                    </span>
                                                @else
                                                    {{ !empty($row['gate']) ? $row['gate'] : '-' }}
                                                @endif
                                            </td>
                                            
                                            <!-- Poin Moto 1 -->
                                            @if($sheetData['columns']['has_poin_moto_1'])
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">
                                                    {{ !empty($row['poin_moto_1']) ? $row['poin_moto_1'] : '-' }}
                                                </td>
                                            @endif
                                            
                                            <!-- Poin Moto 2 -->
                                            @if($sheetData['columns']['has_poin_moto_2'])
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">
                                                    {{ !empty($row['poin_moto_2']) ? $row['poin_moto_2'] : '-' }}
                                                </td>
                                            @endif
                                            
                                             <!-- Poin Moto 3 -->
                                             @if($sheetData['columns']['has_poin_moto_3'])
                                                 <td class="px-4 py-3 whitespace-nowrap text-sm text-center text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">
                                                     {{ !empty($row['poin_moto_3']) ? $row['poin_moto_3'] : '-' }}
                                                 </td>
                                             @endif
                                             
                                             <!-- Total (only for Qualifikasi) -->
                                             @if($sheetData['is_qualifikasi'] ?? false)
                                                 <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-center text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">
                                                     {{ !empty($row['total']) ? $row['total'] : '-' }}
                                                 </td>
                                             @endif
                                             
                                             <!-- Rank -->
                                            <td class="px-4 py-3 whitespace-nowrap text-sm font-bold text-center text-gray-900 dark:text-white border-r border-gray-200 dark:border-gray-700">
                                                {{ !empty($row['rank']) ? $row['rank'] : '-' }}
                                            </td>
                                            
                                            <!-- Ket -->
                                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                {{ !empty($row['ket']) ? $row['ket'] : '-' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                     @php
                                         $colspan = 3; // Plate, Riders, Gate Moto
                                         $colspan += ($sheetData['columns']['has_poin_moto_1'] ?? false) ? 1 : 0;
                                         $colspan += ($sheetData['columns']['has_poin_moto_2'] ?? false) ? 1 : 0;
                                         $colspan += ($sheetData['columns']['has_poin_moto_3'] ?? false) ? 1 : 0;
                                         $colspan += ($sheetData['is_qualifikasi'] ?? false) ? 1 : 0; // Total (only for Qualifikasi)
                                         $colspan += 2; // Rank, Ket
                                     @endphp
                                    <tr>
                                        <td colspan="{{ $colspan }}" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            Tidak ada data
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
            
            @if(empty($sheetData['groups']))
                <div class="text-center py-12">
                    <p class="text-gray-500 dark:text-gray-400">Tidak ada data yang ditemukan.</p>
                </div>
            @endif
        @elseif($selectedRound)
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Gagal Memuat Data</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Tidak dapat mengambil data dari Google Sheets. Silakan coba lagi nanti.
                </p>
            </div>
        @else
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400">
                    Silakan pilih round untuk menampilkan data.
                </p>
            </div>
        @endif
    @else
        <div class="text-center py-12">
            <p class="text-gray-500 dark:text-gray-400">
                Silakan pilih kategori untuk melihat hasil live.
            </p>
        </div>
    @endif
@else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Belum Ada Kategori</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Belum ada kategori live result yang tersedia untuk event ini.
        </p>
    </div>
@endif
