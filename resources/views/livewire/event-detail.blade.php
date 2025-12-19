<div class="min-h-screen py-8" x-data="{ showPackages: false }" @category-selected.window="showPackages = true; setTimeout(() => { document.getElementById('packages-section').scrollIntoView({ behavior: 'smooth', block: 'start' }); }, 100)">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Flash Messages -->
        @if (session()->has('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert" x-data="{ show: true }" x-show="show" x-transition>
                <span class="block sm:inline">{{ session('error') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><title>Close</title><path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/></svg>
                </span>
            </div>
        @endif
        
        <!-- Event Header -->
        <div class="mb-8">
            <a href="{{ route('home') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4 transition-colors duration-200" wire:navigate>
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Kembali ke Daftar Event</span>
            </a>
            <h1 class="text-3xl font-bold text-gray-900">{{ $event->name }}</h1>
        </div>

        <!-- Event Detail Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
            <!-- Left: Event Image -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                @if($event->image)
                    <img src="{{ asset('storage/' . $event->image) }}" alt="{{ $event->name }}" class="w-full h-auto">
                @else
                    <div class="w-full h-96 flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-500">
                        <span class="text-white text-6xl font-bold">{{ substr($event->name, 0, 1) }}</span>
                    </div>
                @endif
            </div>

            <!-- Right: Event Details -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="space-y-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $event->name }}</h2>
                        @if($event->description)
                            <p class="text-gray-600 mb-4">{{ $event->description }}</p>
                        @endif
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 mr-3 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">Lokasi</p>
                                <p class="text-gray-600">{{ $event->location }}</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <svg class="w-5 h-5 mr-3 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">Tanggal Event</p>
                                <p class="text-gray-600">
                                    {{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }} 
                                    @if($event->start_date !== $event->end_date)
                                        - {{ \Carbon\Carbon::parse($event->end_date)->format('d M Y') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <svg class="w-5 h-5 mr-3 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900">Waktu Pendaftaran</p>
                                <p class="text-gray-600">
                                    {{ \Carbon\Carbon::parse($event->registration_start)->setTimezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                                    - {{ \Carbon\Carbon::parse($event->registration_end)->setTimezone('Asia/Jakarta')->format('d M Y H:i') }} WIB
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Google Maps Embed -->
                    <div class="pt-4">
                        <p class="font-medium text-gray-900 mb-2">Lokasi di Google Maps</p>
                        <div class="rounded-lg overflow-hidden shadow-sm">
                            <iframe 
                                width="100%" 
                                height="250" 
                                style="border:0" 
                                loading="lazy" 
                                allowfullscreen
                                referrerpolicy="no-referrer-when-downgrade"
                                src="https://www.google.com/maps?q={{ urlencode($event->location) }}&output=embed">
                            </iframe>
                        </div>
                    </div>

                    <!-- Data Peserta Terdaftar Button -->
                    <div class="pt-4">
                        <button class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200">
                            Data Peserta Terdaftar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories Section -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Kategori Event</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($categories as $category)
                    @php
                        // Calculate quota per category
                        // Total quota = max_participants from category
                        $totalQuota = $category->max_participants ?? 0;
                        
                        // Total registered = count participants who selected this category
                        $totalRegistered = \App\Models\Participant::where('category_id', $category->id)
                            ->whereIn('status', ['pending', 'registered', 'confirmed'])
                            ->count();
                        
                        $percentage = $totalQuota > 0 ? ($totalRegistered / $totalQuota) * 100 : 0;
                        $isFull = $totalQuota > 0 && $totalRegistered >= $totalQuota;
                        $isSelected = $selectedCategoryId == $category->id;
                    @endphp
                    <div 
                        class="bg-white rounded-lg shadow-md p-6 transition-all duration-300 hover:shadow-xl {{ $isSelected ? 'ring-2 ring-blue-500 border-blue-500' : '' }}"
                        x-data="{ hover: false }"
                        @mouseenter="hover = true"
                        @mouseleave="hover = false"
                    >
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ $category->name }}</h3>
                        
                        @if($category->description)
                            <p class="text-gray-600 text-sm mb-4">{{ $category->description }}</p>
                        @endif

                        <!-- Quota Info -->
                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Kuota</span>
                                <span class="text-sm text-gray-600">
                                    {{ $totalRegistered }}/{{ $totalQuota > 0 ? $totalQuota : '∞' }}
                                </span>
                            </div>
                            @if($totalQuota > 0)
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div 
                                        class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                                        :style="`width: {{ min($percentage, 100) }}%`"
                                        x-bind:class="hover ? 'bg-blue-700' : 'bg-blue-600'"
                                    ></div>
                                </div>
                            @else
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-green-500 h-2.5 rounded-full" style="width: 100%"></div>
                                </div>
                            @endif
                        </div>

                        <!-- Select Button -->
                        @if($isFull)
                            <button 
                                disabled
                                class="w-full bg-gray-400 text-white font-medium py-2 px-4 rounded-md cursor-not-allowed"
                            >
                                Kuota Penuh
                            </button>
                        @else
                            @if($isSelected)
                                <button 
                                    disabled
                                    type="button"
                                    class="w-full bg-green-600 text-white font-medium py-2 px-4 rounded-md cursor-default flex items-center justify-center"
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Kategori Terpilih
                                </button>
                            @else
                                <button 
                                    wire:click="selectCategory('{{ $category->id }}')"
                                    type="button"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200"
                                    wire:loading.attr="disabled"
                                    wire:target="selectCategory"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform scale-95"
                                    x-transition:enter-end="opacity-100 transform scale-100"
                                >
                                    <span wire:loading.remove wire:target="selectCategory">Pilih Kategori</span>
                                    <span wire:loading wire:target="selectCategory" class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Memproses...
                                    </span>
                                </button>
                            @endif
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Packages Section (shown when category is selected) -->
        <div id="packages-section" x-show="showPackages" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 transform translate-y-0" x-transition:leave-end="opacity-0 transform translate-y-4">
            @if($selectedCategory)
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <div class="flex justify-between items-center mb-6">
<div>
                            <h2 class="text-2xl font-bold text-gray-900">Paket Registrasi</h2>
                            <p class="text-gray-600 mt-1">Kategori: <span class="font-semibold">{{ $selectedCategory->name }}</span></p>
                        </div>
                        <button 
                            wire:click="clearSelection"
                            class="text-gray-600 hover:text-gray-900 px-4 py-2 rounded-md border border-gray-300 hover:border-gray-400 transition-colors duration-200"
                        >
                            Pilih Kategori Lain
                        </button>
                    </div>

                    @if($selectedCategory->packages->isEmpty())
                        <p class="text-gray-500 text-center py-8">Belum ada paket tersedia untuk kategori ini.</p>
                    @else
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($selectedCategory->packages as $package)
                                <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow duration-200">
                                    <div class="mb-4">
                                        @if($package->image ?? false)
                                            <img src="{{ asset('storage/' . $package->image) }}" alt="{{ $package->name }}" class="w-full h-32 object-cover rounded-md mb-3">
                                        @else
                                            <div class="w-full h-32 bg-gradient-to-br from-purple-400 to-pink-500 rounded-md mb-3 flex items-center justify-center">
                                                <span class="text-white text-2xl font-bold">{{ substr($package->name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                        
                                        <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $package->name }}</h3>
                                        
                                        @if($package->description)
                                            <p class="text-gray-600 text-sm mb-3">{{ $package->description }}</p>
                                        @endif
                                    </div>

                                    <div class="mb-4">
                                        <p class="text-2xl font-bold text-blue-600">
                                            Rp {{ number_format($package->price, 0, ',', '.') }}
                                        </p>
                                    </div>

                                    <a 
                                        href="{{ route('registration.show', ['packageId' => $package->id, 'categoryId' => $selectedCategory->id]) }}" 
                                        wire:navigate
                                        class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200"
                                    >
                                        Lanjutkan ke Registrasi
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
