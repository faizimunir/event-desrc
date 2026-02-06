@extends('layouts.app')

@section('title', $event->name . ' - Live Result')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumb -->
    <nav class="mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2 text-sm">
            <li>
                <a href="{{ route('home') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300" wire:navigate>
                    Home
                </a>
            </li>
            <li>
                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </li>
            <li>
                <a href="{{ route('result.index') }}" class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300" wire:navigate>
                    Live Result
                </a>
            </li>
            <li>
                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                </svg>
            </li>
            <li class="text-gray-900 dark:text-white font-medium">
                {{ $event->name }}
            </li>
        </ol>
    </nav>

    <!-- Event Header -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden mb-8">
        @if($event->image)
            <div class="relative h-64 bg-gray-200 dark:bg-gray-700 overflow-hidden">
                <img 
                    src="{{ asset('storage/' . $event->image) }}" 
                    alt="{{ $event->name }}" 
                    class="w-full h-full object-cover"
                >
            </div>
        @endif

        <div class="p-6">
            <!-- Logo and Event Name Header -->
            <div class="flex items-center gap-4 mb-4">
                @if($event->logo_url)
                    <img 
                        src="{{ asset('storage/' . $event->logo_url) }}" 
                        alt="{{ $event->name }} Logo" 
                        class="h-16 sm:h-20 w-auto object-contain max-w-[150px] flex-shrink-0"
                    >
                @endif
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $event->name }}</h1>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div class="flex items-center text-gray-600 dark:text-gray-300">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span>{{ $event->location }}</span>
                </div>
                <div class="flex items-center text-gray-600 dark:text-gray-300">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>
                        @if($event->is_coming_soon ?? false)
                            <span class="text-blue-600 font-semibold">Coming Soon</span>
                        @elseif($event->start_date)
                            {{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </span>
                </div>
            </div>

            @if($event->description)
                <div class="prose dark:prose-invert max-w-none">
                    <p class="text-gray-600 dark:text-gray-300">{{ $event->description }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Live Result Content -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Hasil Live</h2>
        
        <!-- Loading Indicator (Notification Style) -->
        <div id="loadingIndicator" class="hidden fixed top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
            <span>Memuat data...</span>
                            </div>

        <!-- Content Container -->
        <div>
            @include('live-result.partials.content', ['event' => $event, 'categories' => $categories, 'selectedCategory' => $selectedCategory, 'selectedRound' => $selectedRound, 'sheetData' => $sheetData])
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    'use strict';
    
    // Restore scroll position after page load
    function restoreScrollPosition() {
        const savedScrollPosition = sessionStorage.getItem('liveResultScrollPosition');
        if (savedScrollPosition) {
            const scrollY = parseInt(savedScrollPosition, 10);
            
            // Try to restore immediately
            window.scrollTo(0, scrollY);
            
            // Also try after a short delay to ensure content is rendered
            setTimeout(function() {
                window.scrollTo(0, scrollY);
            }, 50);
            
            // Try again after images/content fully loaded
            setTimeout(function() {
                window.scrollTo(0, scrollY);
                // Clear saved position after restoring
                sessionStorage.removeItem('liveResultScrollPosition');
            }, 200);
        }
    }
    
    // Restore scroll position when page loads
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreScrollPosition);
    } else {
        restoreScrollPosition();
    }
    
    // Also restore after window load (when all resources are loaded)
    window.addEventListener('load', function() {
        const savedScrollPosition = sessionStorage.getItem('liveResultScrollPosition');
        if (savedScrollPosition) {
            window.scrollTo(0, parseInt(savedScrollPosition, 10));
            sessionStorage.removeItem('liveResultScrollPosition');
        }
    });
    
    // Show loading indicator and save scroll position when clicking category or round links
    document.addEventListener('DOMContentLoaded', function() {
        const loadingIndicator = document.getElementById('loadingIndicator');
        
        // Category links
        document.querySelectorAll('.category-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Save current scroll position
                sessionStorage.setItem('liveResultScrollPosition', window.pageYOffset || document.documentElement.scrollTop);
                
                if (loadingIndicator) {
                    loadingIndicator.classList.remove('hidden');
                }
            });
        });
        
        // Round links
        document.querySelectorAll('.round-link').forEach(link => {
            link.addEventListener('click', function(e) {
                // Save current scroll position
                sessionStorage.setItem('liveResultScrollPosition', window.pageYOffset || document.documentElement.scrollTop);
                
                if (loadingIndicator) {
                    loadingIndicator.classList.remove('hidden');
                }
            });
        });
    });
    
    // Auto-refresh for sync checking
    @if($selectedCategory && $selectedRound)
    const categoryId = {{ $selectedCategory->id }};
    const checkSyncUrl = '{{ route("result.check-sync", ["categoryId" => $selectedCategory->id]) }}';
    let lastKnownSyncTimestamp = @json($selectedCategory->last_sync ? $selectedCategory->last_sync->timestamp : null);
    let isRefreshing = false;
    const POLL_INTERVAL = 5000;
    
    // Function to check for sync updates
    async function checkForSync() {
        if (isRefreshing) {
            return;
        }
        
        try {
            const response = await fetch(checkSyncUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                cache: 'no-cache'
            });
            
            if (!response.ok) {
                console.warn('Failed to check sync status:', response.status);
                return;
            }
            
            const data = await response.json();
            
            if (data.success && data.timestamp) {
                // Check if timestamp has changed (new sync detected)
                if (lastKnownSyncTimestamp !== null && data.timestamp !== lastKnownSyncTimestamp) {
                    console.log('Sync detected! Refreshing page...');
                    refreshPage();
                } else if (lastKnownSyncTimestamp === null && data.timestamp !== null) {
                    // First sync detected
                    lastKnownSyncTimestamp = data.timestamp;
                }
            }
        } catch (error) {
            console.error('Error checking sync status:', error);
        }
    }
    
    // Function to refresh the page
    function refreshPage() {
        if (isRefreshing) {
            return; // Prevent multiple refreshes
        }
        
        isRefreshing = true;
        
        // Show notification that page is refreshing
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 flex items-center gap-2';
        notification.innerHTML = `
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Memperbarui data...</span>
        `;
        document.body.appendChild(notification);
        
        // Reload the page after a short delay to show the notification
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
    
    // Start polling when page loads
    if (categoryId) {
        // Initial check after 2 seconds
        setTimeout(() => {
            checkForSync();
        }, 2000);
        
        // Then check every POLL_INTERVAL
        setInterval(checkForSync, POLL_INTERVAL);
    }
    @endif
})();
</script>
@endpush
@endsection

