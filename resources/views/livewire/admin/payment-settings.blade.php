<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Pengaturan Pembayaran</h1>
        <button wire:click="openModal" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
            + Pengaturan Baru
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Bank</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">No. Rekening</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Atas Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Default</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($settings as $setting)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $setting->event ? $setting->event->name : 'Global (Default)' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $setting->bank_name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $setting->account_number }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $setting->account_name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($setting->is_default)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Default</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $setting->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($setting->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button wire:click="openModal({{ $setting->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button wire:click="delete({{ $setting->id }})" wire:confirm="Hapus pengaturan ini?" class="text-red-600 hover:text-red-900">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada pengaturan pembayaran</td>
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
                    <h3 class="text-lg font-bold">{{ $editingId ? 'Edit Pengaturan' : 'Pengaturan Baru' }}</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Event (Opsional, kosongkan untuk default)</label>
                            <select wire:model="event_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Global (Default)</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama Bank</label>
                            <input type="text" wire:model="bank_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('bank_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nomor Rekening</label>
                            <input type="text" wire:model="account_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('account_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Atas Nama</label>
                            <input type="text" wire:model="account_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('account_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="is_default" id="is_default" class="rounded border-gray-300">
                            <label for="is_default" class="ml-2 text-sm text-gray-700">Jadikan Default</label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
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

