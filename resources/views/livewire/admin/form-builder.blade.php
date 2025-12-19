<div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">Form Builder - Kelola Form Paket</h1>

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

    <!-- Package Selection -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Paket</label>
        <select wire:model.live="selectedPackageId" class="w-full rounded-md border-gray-300 shadow-sm">
            <option value="">-- Pilih Paket --</option>
            @foreach($packages as $package)
                <option value="{{ $package->id }}">
                    {{ $package->event->name ?? 'N/A' }} - {{ $package->name }}
                </option>
            @endforeach
        </select>
    </div>

    @if($selectedPackage)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <p class="text-sm font-medium text-blue-900">
                <strong>Paket:</strong> {{ $selectedPackage->name }} | 
                <strong>Event:</strong> {{ $selectedPackage->event->name ?? 'N/A' }}
            </p>
        </div>

        <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-gray-900">Form Fields</h2>
            <button wire:click="openModal" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                + Tambah Field
            </button>
        </div>

        <!-- Form Fields List -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Label</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Required</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($formFields as $field)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $field->order }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $field->label }}</div>
                                @if($field->help_text)
                                    <div class="text-xs text-gray-500">{{ $field->help_text }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $field->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $field->type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($field->required)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Ya</span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Tidak</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $field->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($field->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <button wire:click="openModal({{ $field->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>
                                <button wire:click="delete({{ $field->id }})" wire:confirm="Hapus field ini?" class="text-red-600 hover:text-red-900">Hapus</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada field</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">{{ $editingFieldId ? 'Edit Field' : 'Field Baru' }}</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name (field name)</label>
                            <input type="text" wire:model="name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Label</label>
                            <input type="text" wire:model="label" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('label') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="text">Text</option>
                                <option value="textarea">Textarea</option>
                                <option value="email">Email</option>
                                <option value="tel">Telepon</option>
                                <option value="date">Date</option>
                                <option value="number">Number</option>
                                <option value="select">Select</option>
                                <option value="radio">Radio</option>
                                <option value="checkbox">Checkbox</option>
                            </select>
                        </div>

                        @if(in_array($type, ['select', 'radio']))
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Options (satu per baris)</label>
                                <textarea wire:model="options" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                <p class="text-xs text-gray-500 mt-1">Masukkan setiap opsi dalam baris terpisah</p>
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Help Text</label>
                            <input type="text" wire:model="help_text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="required" id="required" class="rounded border-gray-300">
                            <label for="required" class="ml-2 text-sm text-gray-700">Required</label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Order</label>
                            <input type="number" wire:model="order" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('order') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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

