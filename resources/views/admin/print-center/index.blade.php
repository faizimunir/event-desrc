@extends('admin.layouts.app')

@section('title', 'Cetak Hasil - Print Center')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Cetak Hasil</h1>
        <p class="text-gray-600 dark:text-gray-400">Pilih event untuk mencetak hasil live result (semua kategori pada round final)</p>
    </div>

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 max-w-2xl">
        <form id="printForm" action="{{ route('admin.print-center.preview') }}" method="GET" target="_blank">
            <div class="space-y-6">
                <!-- Pilih Event -->
                <div>
                    <label for="event_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pilih Event <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="event_id" 
                        name="event_id" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">-- Pilih Event --</option>
                        @foreach($events as $event)
                            @if($event->liveResultCategories->count() > 0)
                                <option value="{{ $event->id }}">
                                    {{ $event->name }}
                                </option>
                            @endif
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Preview akan menampilkan semua kategori pada round final
                    </p>
                </div>

                <!-- Tombol Submit -->
                <div class="flex justify-end">
                    <button 
                        type="submit" 
                        id="previewBtn"
                        disabled
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-md transition-colors disabled:bg-gray-400 disabled:cursor-not-allowed"
                    >
                        Buka Preview Cetak
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const eventSelect = document.getElementById('event_id');
    const previewBtn = document.getElementById('previewBtn');

    // Handle event selection
    eventSelect.addEventListener('change', function() {
        const selectedEventId = this.value;
        previewBtn.disabled = !selectedEventId;
    });
});
</script>
@endsection

