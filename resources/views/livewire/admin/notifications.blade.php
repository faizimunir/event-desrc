<div>
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Kelola Notifikasi</h1>
        <button wire:click="openModal" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
            + Template Baru
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Channel</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Default</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($templates as $template)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $template->event ? $template->event->name : 'Global (Default)' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $template->type === 'confirmed' ? 'bg-green-100 text-green-800' : 
                                   ($template->type === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ ucfirst($template->type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst($template->channel) }}</td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900">{{ $template->subject ?: '-' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($template->is_default)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Default</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $template->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($template->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button wire:click="openModal({{ $template->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>
                            <button wire:click="delete({{ $template->id }})" wire:confirm="Hapus template ini?" class="text-red-600 hover:text-red-900">Hapus</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada template notifikasi</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeModal">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">{{ $editingId ? 'Edit Template' : 'Template Baru' }}</h3>
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                <select wire:model="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="pending">Pending</option>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Channel</label>
                                <select wire:model="channel" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="email">Email</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="both">Both</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Subject (untuk Email)</label>
                            <input type="text" wire:model="subject" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Content</label>
                            <textarea wire:model="content" rows="8" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                Anda dapat menggunakan placeholder: {name}, {event_name}, {category_name}, {package_name}, {amount}, {unique_code}, {registration_number}
                            </p>
                            @error('content') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
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

