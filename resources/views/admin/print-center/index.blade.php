@extends('admin.layouts.app')

@section('title', 'Cetak Hasil - Print Center')

@section('content')
<div class="p-6">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Cetak Hasil</h1>
        <p class="text-gray-600 dark:text-gray-400">Pilih kategori dan round untuk mencetak hasil live result</p>
    </div>

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 max-w-2xl">
        <form id="printForm" action="{{ route('admin.print-center.preview') }}" method="GET" target="_blank">
            <div class="space-y-6">
                <!-- Pilih Kategori -->
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pilih Kategori <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="category_id" 
                        name="category_id" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">-- Pilih Kategori --</option>
                        @foreach($events as $event)
                            @if($event->liveResultCategories->count() > 0)
                                <optgroup label="{{ $event->name }}">
                                    @foreach($event->liveResultCategories as $category)
                                        <option value="{{ $category->id }}" data-event-id="{{ $event->id }}" data-sheets="{{ json_encode($category->selected_sheets) }}">
                                            {{ $category->title }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Pilih Round (Dinamis) -->
                <div>
                    <label for="round" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pilih Round <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="round" 
                        name="round" 
                        required
                        disabled
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-900 disabled:cursor-not-allowed"
                    >
                        <option value="">-- Pilih Kategori terlebih dahulu --</option>
                    </select>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Round akan muncul setelah Anda memilih kategori
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
    const categorySelect = document.getElementById('category_id');
    const roundSelect = document.getElementById('round');
    const previewBtn = document.getElementById('previewBtn');

    categorySelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            const sheets = JSON.parse(selectedOption.getAttribute('data-sheets') || '[]');
            
            // Clear existing options
            roundSelect.innerHTML = '<option value="">-- Pilih Round --</option>';
            
            // Add round options
            if (sheets.length > 0) {
                sheets.forEach(function(sheet) {
                    const option = document.createElement('option');
                    option.value = sheet;
                    option.textContent = sheet;
                    roundSelect.appendChild(option);
                });
                roundSelect.disabled = false;
            } else {
                roundSelect.innerHTML = '<option value="">Tidak ada round tersedia</option>';
                roundSelect.disabled = true;
            }
        } else {
            roundSelect.innerHTML = '<option value="">-- Pilih Kategori terlebih dahulu --</option>';
            roundSelect.disabled = true;
        }
        
        // Enable/disable preview button
        previewBtn.disabled = !roundSelect.value || roundSelect.disabled;
    });

    roundSelect.addEventListener('change', function() {
        previewBtn.disabled = !this.value || this.disabled;
    });
});
</script>
@endsection

