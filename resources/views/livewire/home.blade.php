<div class="min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Daftar Event</h1>
            <p class="text-gray-600 dark:text-gray-400">Pilih event yang ingin Anda ikuti</p>
        </div>

        @if($events->isEmpty())
            <div class="text-center py-12">
                <p class="text-gray-500 dark:text-gray-400 text-lg">Belum ada event yang tersedia saat ini.</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" 
                 x-data="{ visibleCount: 6 }"
                 x-init="
                    // Intersection Observer for lazy loading
                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                const img = entry.target;
                                if (img.dataset.src) {
                                    img.src = img.dataset.src;
                                    img.removeAttribute('data-src');
                                    observer.unobserve(img);
                                }
                            }
                        });
                    });
                    document.querySelectorAll('img[data-src]').forEach(img => observer.observe(img));
                 ">
                @foreach($events as $index => $event)
                    <div x-show="{{ $index }} < visibleCount" 
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100">
                        <x-event-card :event="$event" />
                    </div>
                @endforeach
                
                <!-- Lazy load more events -->
                @if($events->count() > 6)
                    <div x-show="visibleCount < {{ $events->count() }}" 
                         x-cloak
                         class="col-span-full text-center mt-6">
                        <button 
                            @click="visibleCount += 6"
                            class="bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium py-2 px-6 rounded-md transition-colors duration-200"
                        >
                            Muat Lebih Banyak
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
