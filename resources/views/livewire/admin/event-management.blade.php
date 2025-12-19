<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Kelola Event</h1>
        <button 
            wire:click="openModal"
            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md"
        >
            + Event Baru
        </button>
    </div>

    @if(session()->has('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama Event</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Lokasi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pendaftaran</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($events as $event)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $event->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-500">{{ $event->start_date->format('d M Y') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">{{ $event->location }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $event->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($event->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button 
                                wire:click="toggleRegistration({{ $event->id }})"
                                class="px-3 py-1 text-xs font-medium rounded-md transition-colors
                                    {{ $event->registration_open 
                                        ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                        : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}"
                            >
                                {{ $event->registration_open ? 'Terbuka' : 'Tertutup' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <a href="{{ route('admin.live-result-categories.index', $event->id) }}" class="text-green-600 hover:text-green-900">Live Result</a>
                            <button wire:click="openModal({{ $event->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button wire:click="delete({{ $event->id }})" wire:confirm="Apakah Anda yakin ingin menghapus event ini?" class="text-red-600 hover:text-red-900">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada event</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">{{ $editingId ? 'Edit Event' : 'Event Baru' }}</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Event</label>
                            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                            <textarea wire:model="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Mulai</label>
                                <input type="date" wire:model="start_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @error('start_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tanggal Selesai</label>
                                <input type="date" wire:model="end_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @error('end_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Registrasi Mulai</label>
                                <input type="datetime-local" wire:model="registration_start" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @error('registration_start') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Registrasi Selesai</label>
                                <input type="datetime-local" wire:model="registration_end" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @error('registration_end') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="registration_open" id="registration_open" class="rounded border-gray-300">
                            <label for="registration_open" class="ml-2 text-sm text-gray-700">
                                Buka Pendaftaran Secara Manual (abaikan waktu pendaftaran)
                            </label>
                        </div>
                        <p class="text-xs text-gray-500">Jika dicentang, pendaftaran akan selalu terbuka terlepas dari waktu yang ditentukan di atas.</p>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Lokasi</label>
                            <input type="text" wire:model="location" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('location') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Poster</label>
                            <input type="file" wire:model="image" accept="image/*" class="mt-1 block w-full">
                            @error('image') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Logo Event</label>
                            <input type="file" wire:model="logo" accept="image/jpeg,image/jpg,image/png,image/gif" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="mt-1 text-xs text-gray-500">Format: JPG, JPEG, PNG, GIF. Maksimal 2MB</p>
                            @error('logo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            
                            <!-- Logo Preview -->
                            @if($logoPreview)
                                <div class="mt-3 flex items-center gap-4">
                                    <div class="relative">
                                        <img src="{{ $logoPreview }}" alt="Logo Preview" class="h-20 w-auto object-contain border border-gray-300 rounded-md p-2 bg-white">
                                    </div>
                                    @if($editingId)
                                        <button type="button" wire:click="removeLogo" class="text-sm text-red-600 hover:text-red-800 font-medium">
                                            Hapus Logo
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="closed">Closed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="closeModal" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

