@props(['participants', 'showActions' => true])

<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-800">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    No. Registrasi
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Nama
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Email
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Paket
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                    Status
                </th>
                @if($showActions)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                        Aksi
                    </th>
                @endif
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($participants as $participant)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {{ $participant->registration_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                        {{ $participant->name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                        {{ $participant->email }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                        {{ $participant->package->name ?? '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                'registered' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                'confirmed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                            ];
                            $color = $statusColors[$participant->status] ?? 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
                        @endphp
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }}">
                            {{ ucfirst($participant->status) }}
                        </span>
                    </td>
                    @if($showActions)
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="#" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                Detail
                            </a>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showActions ? 6 : 5 }}" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                        Tidak ada data peserta
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

