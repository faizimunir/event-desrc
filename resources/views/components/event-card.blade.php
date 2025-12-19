@props(['event'])

<div 
    class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl {{ $event->is_closed ? 'grayscale opacity-75' : '' }}"
    x-data="{ hover: false }"
    @mouseenter="hover = true"
    @mouseleave="hover = false"
>
    <!-- Event Image -->
    <div class="relative h-48 bg-gray-200 dark:bg-gray-700 overflow-hidden">
        @if($event->image)
            <img 
                data-src="{{ asset('storage/' . $event->image) }}" 
                src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='400' height='300'%3E%3Crect fill='%23f3f4f6' width='400' height='300'/%3E%3C/svg%3E"
                alt="{{ $event->name }}" 
                class="w-full h-full object-cover"
                loading="lazy"
            >
        @else
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-500">
                <span class="text-white text-4xl font-bold">{{ substr($event->name, 0, 1) }}</span>
            </div>
        @endif
        
        @if($event->is_closed)
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                <span class="text-white text-2xl font-bold uppercase">CLOSED</span>
            </div>
        @endif
    </div>

    <!-- Event Content -->
    <div class="p-6">
        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">{{ $event->name }}</h3>
        
        <div class="space-y-2 mb-4 text-sm text-gray-600 dark:text-gray-300">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ $event->location }}</span>
            </div>
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>{{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}</span>
            </div>
        </div>

        @if($event->description)
            <p class="text-gray-600 dark:text-gray-300 text-sm mb-4 line-clamp-2">{{ Str::limit($event->description, 100) }}</p>
        @endif

        @if(!$event->is_closed && $event->is_open)
            <a 
                href="{{ route('event.detail', $event->id) }}" 
                wire:navigate
                class="block w-full text-center bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-md transition-colors duration-200"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
            >
                View & Registrasi
            </a>
        @elseif($event->is_closed)
            <button 
                disabled
                class="block w-full text-center bg-gray-400 dark:bg-gray-600 text-white font-medium py-2 px-4 rounded-md cursor-not-allowed"
            >
                Event Ditutup
            </button>
        @else
            <button 
                disabled
                class="block w-full text-center bg-gray-400 dark:bg-gray-600 text-white font-medium py-2 px-4 rounded-md cursor-not-allowed"
            >
                Registrasi Belum Dibuka
            </button>
        @endif
    </div>
</div>

