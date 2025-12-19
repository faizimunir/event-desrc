@extends('layouts.app')

@section('title', 'Live Result')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Live Result</h1>
        <p class="text-gray-600 dark:text-gray-300">Lihat hasil live dari berbagai event yang telah berlangsung</p>
    </div>

    <!-- Events Grid -->
    @if($events->count() > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($events as $event)
                @if($event->slug)
                <a 
                    href="/{{ $event->slug }}" 
                    class="block bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:shadow-xl hover:scale-105"
                >
                    <!-- Event Image -->
                    <div class="relative h-48 bg-gray-200 dark:bg-gray-700 overflow-hidden">
                        @if($event->image)
                            <img 
                                src="{{ asset('storage/' . $event->image) }}" 
                                alt="{{ $event->name }}" 
                                class="w-full h-full object-cover"
                                loading="lazy"
                            >
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-blue-400 to-purple-500">
                                <span class="text-white text-4xl font-bold">{{ substr($event->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- Event Content -->
                    <div class="p-6">
                        <!-- Logo and Event Name Header -->
                        <div class="flex items-center gap-3 mb-3">
                            @if($event->logo_url)
                                <img 
                                    src="{{ asset('storage/' . $event->logo_url) }}" 
                                    alt="{{ $event->name }} Logo" 
                                    class="h-12 sm:h-16 w-auto object-contain max-w-[120px] flex-shrink-0"
                                    loading="lazy"
                                >
                            @endif
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white line-clamp-2 flex-1">
                                {{ $event->name }}
                            </h3>
                        </div>
                        
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-300">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span>{{ \Carbon\Carbon::parse($event->start_date)->format('d M Y') }}</span>
                        </div>
                    </div>
                </a>
                @endif
            @endforeach
        </div>
    @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Tidak ada event</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Belum ada event yang tersedia untuk live result.</p>
        </div>
    @endif
</div>
@endsection

