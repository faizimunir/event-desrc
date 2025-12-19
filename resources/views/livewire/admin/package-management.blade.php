<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Kelola Paket</h1>
        <button 
            type="button"
            wire:click="openModal"
            @if(!$eventFilter) disabled @endif
            wire:loading.attr="disabled"
            wire:target="openModal"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
        >
            <svg wire:loading wire:target="openModal" class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span wire:loading.remove wire:target="openModal">+ Paket Baru</span>
            <span wire:loading wire:target="openModal">Loading...</span>
        </button>
    </div>

    @if(session()->has('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter Event</label>
                <select wire:model.live="eventFilter" class="w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Semua Event</option>
                    @foreach($events as $event)
                        <option value="{{ $event->id }}">{{ $event->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Paket</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kategori</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Harga</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($packages as $package)
                        <tr>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $package->name }}</div>
                                @if($package->description)
                                    <div class="text-xs text-gray-500 mt-1">{{ Str::limit($package->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $package->event->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $package->category->name ?? '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">Rp {{ number_format($package->price, 0, ',', '.') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $package->status === 'active' ? 'bg-green-100 text-green-800' : 
                                       ($package->status === 'sold_out' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                    {{ ucfirst(str_replace('_', ' ', $package->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button wire:click="openModal({{ $package->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>
                                <button wire:click="delete({{ $package->id }})" wire:confirm="Apakah Anda yakin ingin menghapus paket ini?" class="text-red-600 hover:text-red-900">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada paket</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">{{ $editingId ? 'Edit Paket' : 'Paket Baru' }}</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Event *</label>
                            @if($editingId)
                                <select wire:model="event_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    <option value="">Pilih Event</option>
                                    @foreach($events as $event)
                                        <option value="{{ $event->id }}">{{ $event->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                @php
                                    $selectedEventId = $event_id ?? $eventFilter ?? '';
                                    $selectedEventName = $selectedEventId ? ($events->firstWhere('id', $selectedEventId)?->name ?? 'Event yang dipilih') : 'Pilih event terlebih dahulu';
                                @endphp
                                <input type="text" value="{{ $selectedEventName }}" readonly class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm bg-gray-100 dark:bg-gray-600 cursor-not-allowed">
                                <input type="hidden" wire:model="event_id">
                                @if(!$eventFilter)
                                    <p class="text-xs text-red-500 dark:text-red-400 mt-1">Silakan pilih event terlebih dahulu di filter di atas</p>
                                @else
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Event dipilih dari filter di atas</p>
                                @endif
                            @endif
                            @error('event_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Paket</label>
                            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                            <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Harga</label>
                            <input type="number" wire:model="price" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('price') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="sold_out">Sold Out</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button 
                            type="button" 
                            wire:click="closeModal"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Batal
                        </button>
                        <button 
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="save"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                        >
                            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="save">Simpan</span>
                            <span wire:loading wire:target="save">Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

