<div>

    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('admin.registrations.index') }}" class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mb-4 transition-colors duration-200">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Kembali ke Data Registrasi</span>
        </a>
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Detail Registrasi Peserta</h1>
            <div class="flex space-x-3">
                <button 
                    wire:click="openEditModal"
                    type="button"
                    wire:loading.attr="disabled"
                    wire:target="openEditModal"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-md shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    <span wire:loading.remove wire:target="openEditModal">Edit Data</span>
                    <span wire:loading wire:target="openEditModal">Loading...</span>
                </button>
                <button 
                    wire:click="openDeleteModal"
                    type="button"
                    wire:loading.attr="disabled"
                    wire:target="openDeleteModal"
                    class="inline-flex items-center px-4 py-2 bg-red-700 hover:bg-red-800 dark:bg-red-600 dark:hover:bg-red-700 text-white font-medium rounded-md shadow-md border border-red-800 dark:border-red-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                    style="background-color: #dc2626;"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span wire:loading.remove wire:target="openDeleteModal">Hapus</span>
                    <span wire:loading wire:target="openDeleteModal">Loading...</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="mb-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded relative" role="alert" x-data="{ show: true }" x-show="show" x-transition>
            <span class="block sm:inline">{{ session('success') }}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative" role="alert" x-data="{ show: true }" x-show="show" x-transition>
            <span class="block sm:inline">{{ session('error') }}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
            </span>
        </div>
    @endif

    @if(!$participant)
        <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4">
            <p>Data peserta tidak ditemukan atau tidak dapat diakses.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Info -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Registration Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Informasi Registrasi</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">No. Registrasi</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $participant->registration_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Kode Unik</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $participant->unique_code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Status</label>
                            <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                                {{ $participant->status === 'confirmed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                   ($participant->status === 'registered' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                   ($participant->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200')) }}">
                                {{ ucfirst($participant->status) }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal Registrasi</label>
                            <p class="text-gray-900 dark:text-white">{{ $participant->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Participant Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Data Peserta</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nama Lengkap</label>
                            <p class="text-gray-900 dark:text-white">{{ $participant->name }}</p>
                        </div>
                        @if($participant->nickname)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Nama Panggilan</label>
                                <p class="text-gray-900 dark:text-white">{{ $participant->nickname }}</p>
                            </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Email</label>
                            <p class="text-gray-900 dark:text-white">{{ $participant->email }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Telepon</label>
                            <p class="text-gray-900 dark:text-white">{{ $participant->phone }}</p>
                        </div>
                        @if($participant->number_plate)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Number Plate</label>
                                <p class="text-gray-900 dark:text-white">{{ $participant->number_plate }}</p>
                            </div>
                        @endif
                        @if($participant->komunitas)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Komunitas</label>
                                <p class="text-gray-900 dark:text-white">{{ $participant->komunitas }}</p>
                            </div>
                        @endif
                        @if($participant->address)
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Alamat</label>
                                <p class="text-gray-900 dark:text-white">{{ $participant->address }}</p>
                            </div>
                        @endif
                        @if($participant->date_of_birth)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Tanggal Lahir</label>
                                <p class="text-gray-900 dark:text-white">{{ $participant->date_of_birth->format('d M Y') }}</p>
                            </div>
                        @endif
                        @if($participant->gender)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Jenis Kelamin</label>
                                <p class="text-gray-900 dark:text-white">{{ $participant->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}</p>
                            </div>
                        @endif
                        @if($participant->emergency_contact_name)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Kontak Darurat (Nama)</label>
                                <p class="text-gray-900 dark:text-white">{{ $participant->emergency_contact_name }}</p>
                            </div>
                        @endif
                        @if($participant->emergency_contact_phone)
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Kontak Darurat (Telepon)</label>
                                <p class="text-gray-900 dark:text-white">{{ $participant->emergency_contact_phone }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Event & Package Info -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Event & Paket</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Event</label>
                            <p class="text-gray-900 dark:text-white font-medium">{{ $participant->category->event->name ?? $participant->package->event->name ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Kategori</label>
                            <p class="text-gray-900 dark:text-white">{{ $participant->category->name ?? $participant->package->category->name ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Paket</label>
                            <p class="text-gray-900 dark:text-white">{{ $participant->package->name ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Harga</label>
                            <p class="text-lg font-semibold text-gray-900 dark:text-white">Rp {{ number_format($participant->package->price ?? 0, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Pembayaran</label>
                            <p class="text-lg font-semibold text-blue-600 dark:text-blue-400">Rp {{ number_format(($participant->package->price ?? 0) + (int)$participant->unique_code, 0, ',', '.') }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Info -->
                @if($participant->payment)
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Informasi Pembayaran</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Status Pembayaran</label>
                                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full
                                    {{ $participant->payment->status === 'verified' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                       ($participant->payment->status === 'paid' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                       ($participant->payment->status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200')) }}">
                                    {{ ucfirst($participant->payment->status) }}
                                </span>
                            </div>
                            @if($participant->payment->payment_proof)
                                <div>
                                    <label class="block text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Bukti Pembayaran</label>
                                    <a href="{{ asset('storage/' . $participant->payment->payment_proof) }}" target="_blank" class="block">
                                        <img src="{{ asset('storage/' . $participant->payment->payment_proof) }}" alt="Bukti Pembayaran" class="w-full rounded-md border border-gray-200 dark:border-gray-700">
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Edit Modal -->
    @if($showEditModal && $participant)
        <div class="fixed inset-0 z-50 overflow-y-auto" style="display: block !important; position: fixed; top: 0; left: 0; right: 0; bottom: 0;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" 
                    wire:click="closeEditModal" 
                    style="cursor: pointer; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 40;"
                ></div>

                <div 
                    class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full z-50" 
                    wire:click.stop 
                    wire:key="edit-modal"
                    style="position: relative; z-index: 50;"
                >
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-white">Edit Data Peserta</h3>
                            <button type="button" wire:click="closeEditModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <form wire:submit.prevent="update">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap *</label>
                                    <input type="text" wire:model="name" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nama Panggilan</label>
                                    <input type="text" wire:model="nickname" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    @error('nickname') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Number Plate</label>
                                    <input type="text" wire:model="number_plate" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    @error('number_plate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Komunitas</label>
                                    <input type="text" wire:model="komunitas" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    @error('komunitas') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                                    <input type="email" wire:model="email" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Telepon *</label>
                                    <input type="text" wire:model="phone" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alamat</label>
                                    <textarea wire:model="address" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm"></textarea>
                                    @error('address') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tanggal Lahir</label>
                                    <input type="date" wire:model="date_of_birth" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    @error('date_of_birth') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Jenis Kelamin</label>
                                    <select wire:model="gender" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                        <option value="">Pilih</option>
                                        <option value="male">Laki-laki</option>
                                        <option value="female">Perempuan</option>
                                    </select>
                                    @error('gender') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kontak Darurat (Nama)</label>
                                    <input type="text" wire:model="emergency_contact_name" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    @error('emergency_contact_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kontak Darurat (Telepon)</label>
                                    <input type="text" wire:model="emergency_contact_phone" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                    @error('emergency_contact_phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status *</label>
                                    <select wire:model="status" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                        <option value="pending">Pending</option>
                                        <option value="registered">Registered</option>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                    @error('status') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategori *</label>
                                    @php
                                        $event = $participant->category ? $participant->category->event : ($participant->package ? $participant->package->event : null);
                                        $categories = $event ? $event->categories : collect();
                                    @endphp
                                    <select wire:model="category_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                        <option value="">Pilih Kategori</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Paket *</label>
                                    <select wire:model="package_id" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm">
                                        @if($this->packages && $this->packages->count() > 0)
                                            @foreach($this->packages as $package)
                                                <option value="{{ $package->id }}">{{ $package->name }} - Rp {{ number_format($package->price, 0, ',', '.') }}</option>
                                            @endforeach
                                        @else
                                            <option value="{{ $participant->package_id ?? '' }}">{{ $participant->package->name ?? 'Paket saat ini' }}</option>
                                        @endif
                                    </select>
                                    @error('package_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button type="button" wire:click="closeEditModal" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500 transition-colors duration-200">
                                    Batal
                                </button>
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-md transition-colors duration-200">
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" style="display: block !important; position: fixed; top: 0; left: 0; right: 0; bottom: 0;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity" 
                    wire:click="closeDeleteModal" 
                    style="cursor: pointer; position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: 40;"
                ></div>

                <div 
                    class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full z-50" 
                    wire:click.stop 
                    wire:key="delete-modal"
                    style="position: relative; z-index: 50;"
                >
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left flex-1">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Hapus Data Peserta</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        Apakah Anda yakin ingin menghapus data peserta <strong class="text-gray-900 dark:text-white">{{ $participant->name ?? '' }}</strong>? Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data terkait termasuk pembayaran.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                        <button 
                            type="button"
                            wire:click="delete" 
                            wire:loading.attr="disabled"
                            wire:target="delete"
                            class="w-full inline-flex justify-center items-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200"
                            style="background-color: #dc2626;"
                        >
                            <span wire:loading.remove wire:target="delete" class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Hapus
                            </span>
                            <span wire:loading wire:target="delete" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Menghapus...
                            </span>
                        </button>
                        <button 
                            type="button"
                            wire:click="closeDeleteModal"
                            wire:loading.attr="disabled"
                            wire:target="delete"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50 disabled:cursor-not-allowed sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors duration-200"
                        >
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

