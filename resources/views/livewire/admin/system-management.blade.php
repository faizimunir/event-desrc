<div>
    <h1 class="text-3xl font-bold text-gray-900 mb-6">System Management</h1>

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

    <!-- Tabs -->
    @if(auth('admin')->user()->isSuperAdmin())
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                <button 
                    wire:click="$set('activeTab', 'admins')"
                    class="{{ $activeTab === 'admins' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                >
                    Kelola Admin
                </button>
                <button 
                    wire:click="$set('activeTab', 'fees')"
                    class="{{ $activeTab === 'fees' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm"
                >
                    Biaya Admin per Event
                </button>
            </nav>
        </div>
    @endif

    <!-- Admin Management Tab -->
    @if($activeTab === 'admins')
        <div>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Kelola Admin</h2>
                <button wire:click="openAdminModal" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                    + Admin Baru
                </button>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($admins as $admin)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $admin->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $admin->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $admin->role === 'super_admin' ? 'bg-purple-100 text-purple-800' : ($admin->role === 'co_admin_event' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800') }}">
                                        {{ ucfirst(str_replace('_', ' ', $admin->role)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        @if($admin->eventAccess && $admin->eventAccess->count() > 0)
                                            {{ $admin->eventAccess->pluck('name')->join(', ') }}
                                        @elseif($admin->event)
                                            {{ $admin->event->name }}
                                        @else
                                            -
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        {{ $admin->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($admin->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button wire:click="openAdminModal({{ $admin->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>
                                    @if($admin->id != Auth::guard('admin')->id())
                                        <button wire:click="deleteAdmin({{ $admin->id }})" wire:confirm="Hapus admin ini?" class="text-red-600 hover:text-red-900">Hapus</button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">Belum ada admin</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Admin Fees Tab -->
    @if($activeTab === 'fees' && auth('admin')->user()->isSuperAdmin())
        <div>
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Biaya Admin per Event</h2>
                <button wire:click="openFeeModal" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md">
                    + Biaya Baru
                </button>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Deskripsi</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($adminFees as $fee)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $fee->event->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        {{ ucfirst($fee->fee_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if($fee->fee_type === 'fixed')
                                            Rp {{ number_format($fee->fee_amount, 0, ',', '.') }}
                                        @else
                                            {{ $fee->fee_percentage }}%
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-500">{{ $fee->description ?: '-' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <button wire:click="openFeeModal({{ $fee->id }})" class="text-blue-600 hover:text-blue-900">Edit</button>
                                    <button wire:click="deleteFee({{ $fee->id }})" wire:confirm="Hapus biaya admin ini?" class="text-red-600 hover:text-red-900">Hapus</button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Belum ada biaya admin</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Admin Modal -->
    @if($showAdminModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeAdminModal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">{{ $editingAdminId ? 'Edit Admin' : 'Admin Baru' }}</h3>
                    <button wire:click="closeAdminModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form wire:submit.prevent="saveAdmin">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Nama</label>
                            <input type="text" wire:model="adminName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('adminName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" wire:model="adminEmail" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('adminEmail') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Password {{ $editingAdminId ? '(kosongkan jika tidak diubah)' : '' }}</label>
                            <input type="password" wire:model="adminPassword" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @error('adminPassword') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role</label>
                            @if(auth('admin')->user()->isSuperAdmin())
                                <select wire:model="adminRole" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="super_admin">Super Admin</option>
                                    <option value="admin_event">Admin Event</option>
                                    <option value="co_admin_event">Co Admin Event</option>
                                </select>
                            @elseif(auth('admin')->user()->isAdminEvent())
                                @if($editingAdminId)
                                    <select wire:model="adminRole" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="admin_event">Admin Event</option>
                                        <option value="co_admin_event">Co Admin Event</option>
                                    </select>
                                @else
                                    <select wire:model="adminRole" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm bg-gray-100" disabled>
                                        <option value="co_admin_event">Co Admin Event</option>
                                    </select>
                                    <input type="hidden" wire:model="adminRole" value="co_admin_event">
                                    <p class="text-xs text-gray-500 mt-1">Admin Event hanya dapat membuat Co Admin Event</p>
                                @endif
                            @else
                                <select wire:model="adminRole" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="co_admin_event">Co Admin Event</option>
                                </select>
                            @endif
                        </div>

                        @if(in_array($adminRole, ['admin_event', 'co_admin_event']))
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Event (dapat memilih beberapa)</label>
                                <div class="max-h-60 overflow-y-auto border border-gray-300 rounded-md p-3 space-y-2">
                                    @forelse($events as $event)
                                        <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded">
                                            <input 
                                                type="checkbox" 
                                                wire:model="adminSelectedEventIds" 
                                                value="{{ $event->id }}"
                                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            >
                                            <span class="text-sm text-gray-700">{{ $event->name }}</span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-gray-500">Tidak ada event yang tersedia</p>
                                    @endforelse
                                </div>
                                @error('adminSelectedEventIds') 
                                    <span class="text-red-500 text-xs">{{ $message }}</span> 
                                @enderror
                                @error('adminSelectedEventIds.*') 
                                    <span class="text-red-500 text-xs">{{ $message }}</span> 
                                @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="adminStatus" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="closeAdminModal" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
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

    <!-- Fee Modal -->
    @if($showFeeModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="closeFeeModal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold">{{ $editingFeeId ? 'Edit Biaya' : 'Biaya Baru' }}</h3>
                    <button wire:click="closeFeeModal" class="text-gray-400 hover:text-gray-600">&times;</button>
                </div>

                <form wire:submit.prevent="saveFee">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Event</label>
                            <select wire:model="feeEventId" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Pilih Event</option>
                                @foreach($events as $event)
                                    <option value="{{ $event->id }}">{{ $event->name }}</option>
                                @endforeach
                            </select>
                            @error('feeEventId') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Tipe Biaya</label>
                            <select wire:model="feeType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="fixed">Fixed (Rp)</option>
                                <option value="percentage">Percentage (%)</option>
                            </select>
                        </div>

                        @if($feeType === 'fixed')
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Jumlah (Rp)</label>
                                <input type="number" wire:model="feeAmount" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @error('feeAmount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @else
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Persentase (%)</label>
                                <input type="number" wire:model="feePercentage" min="0" max="100" step="0.01" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @error('feePercentage') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Deskripsi</label>
                            <textarea wire:model="feeDescription" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" wire:click="closeFeeModal" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
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

