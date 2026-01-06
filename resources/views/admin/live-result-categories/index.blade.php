@extends('admin.layouts.app')

@section('title', 'Kelola Live Result Categories - ' . $event->name)

@section('content')
<div class="p-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Live Result Categories</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">Event: {{ $event->name }}</p>
        </div>
        <div class="flex gap-2">
            @if($categories->count() > 0)
                <form action="{{ route('admin.live-result-categories.sync-all', $event->id) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-md transition-colors">
                        Update All
                    </button>
                </form>
            @endif
            <a href="{{ route('admin.events.index') }}" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors">
                Kembali ke Events
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Add Category Form -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4">Tambah Kategori Baru</h2>
        <form id="categoryForm" action="{{ route('admin.live-result-categories.store', $event->id) }}" method="POST" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Judul Kategori <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                        placeholder="Contoh: Tournament 2023"
                    >
                </div>
                <div>
                    <label for="spreadsheet_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Spreadsheet ID <span class="text-red-500">*</span>
                    </label>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="spreadsheet_id" 
                            name="spreadsheet_id" 
                            required
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                            placeholder="ID dari URL Google Sheets"
                        >
                        <button 
                            type="button" 
                            id="fetchSheetsBtn"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors"
                        >
                            Fetch Sheets
                        </button>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Contoh: dari URL <code class="bg-gray-100 dark:bg-gray-700 px-1 rounded">https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit</code>
                    </p>
                </div>
            </div>

            <!-- Loading Indicator -->
            <div id="loadingIndicator" class="hidden">
                <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                    <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Mengambil daftar sheet...</span>
                </div>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <span id="errorText"></span>
            </div>

            <!-- Sheets Checkbox Container -->
            <div id="sheetsContainer" class="hidden">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Pilih Sheet yang Akan Ditampilkan:
                </label>
                <div id="sheetsCheckboxes" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 p-4 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 max-h-60 overflow-y-auto">
                    <!-- Checkboxes will be inserted here -->
                </div>
            </div>

            <button 
                type="submit" 
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors"
            >
                Tambah Kategori
            </button>
        </form>
    </div>

    <!-- Categories List -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Daftar Kategori</h2>
        </div>
        
        @if($categories->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Print
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Judul
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Spreadsheet ID
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Selected Sheets
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Last Sync
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Status
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($categories as $category)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($category->selected_sheets && is_array($category->selected_sheets) && count($category->selected_sheets) > 0)
                                        <div class="flex items-center gap-2">
                                            <select 
                                                id="printSheet_{{ $category->id }}" 
                                                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                                            >
                                                <option value="">Pilih Sheet</option>
                                                @foreach($category->selected_sheets as $sheet)
                                                    <option value="{{ htmlspecialchars($sheet, ENT_QUOTES, 'UTF-8') }}">{{ $sheet }}</option>
                                                @endforeach
                                            </select>
                                            <button 
                                                type="button" 
                                                onclick="printCategorySheet({{ $category->id }}, {{ $event->id }})"
                                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors"
                                                title="Print"
                                            >
                                                Print
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500 italic">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $category->title }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                                        {{ Str::limit($category->spreadsheet_id, 30) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($category->selected_sheets && count($category->selected_sheets) > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($category->selected_sheets as $sheet)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                    {{ $sheet }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500 italic">Belum dipilih</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($category->last_sync)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $category->last_sync->format('d M Y H:i') }}
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400 dark:text-gray-500 italic">Belum pernah</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($category->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                            Aktif
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                            Nonaktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        <form 
                                            action="{{ route('admin.live-result-categories.sync', [$event->id, $category->id]) }}" 
                                            method="POST" 
                                            class="inline"
                                        >
                                            @csrf
                                            <button 
                                                type="submit" 
                                                class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300"
                                                title="Sync Data"
                                            >
                                                Sync
                                            </button>
                                        </form>
                                        <form 
                                            action="{{ route('admin.live-result-categories.destroy', [$event->id, $category->id]) }}" 
                                            method="POST" 
                                            class="inline"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button 
                                                type="submit" 
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                            >
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-8 text-center">
                <p class="text-gray-500 dark:text-gray-400">Belum ada kategori. Silakan tambahkan kategori baru di atas.</p>
            </div>
        @endif
    </div>
</div>

<!-- Print Modal -->
<div id="printModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white" id="printModalTitle">Pilih Round untuk Print</h3>
                <button onclick="closePrintModal()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form id="printForm" method="GET" target="_blank">
                <div class="mb-4">
                    <label for="printRound" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Pilih Round <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="printRound" 
                        name="round" 
                        required
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">-- Pilih Round --</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button 
                        type="button" 
                        onclick="closePrintModal()"
                        class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-md transition-colors"
                    >
                        Batal
                    </button>
                    <button 
                        type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors"
                    >
                        Print
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Print Category Sheet Function - Global scope
    function printCategorySheet(categoryId, eventId) {
        const selectElement = document.getElementById('printSheet_' + categoryId);
        if (!selectElement) {
            alert('Element tidak ditemukan.');
            return;
        }
        const selectedSheet = selectElement.value;
        
        if (!selectedSheet) {
            alert('Silakan pilih sheet terlebih dahulu.');
            return;
        }
        
        // Redirect to print route with round parameter
        const printUrl = '{{ route("admin.live-result-categories.print", [$event->id, ":categoryId"]) }}'.replace(':categoryId', categoryId) + '?round=' + encodeURIComponent(selectedSheet);
        window.open(printUrl, '_blank');
    }

document.addEventListener('DOMContentLoaded', function() {
    const fetchSheetsBtn = document.getElementById('fetchSheetsBtn');
    const spreadsheetIdInput = document.getElementById('spreadsheet_id');
    const loadingIndicator = document.getElementById('loadingIndicator');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const sheetsContainer = document.getElementById('sheetsContainer');
    const sheetsCheckboxes = document.getElementById('sheetsCheckboxes');

    fetchSheetsBtn.addEventListener('click', function() {
        const spreadsheetId = spreadsheetIdInput.value.trim();
        
        if (!spreadsheetId) {
            alert('Silakan masukkan Spreadsheet ID terlebih dahulu');
            return;
        }

        // Show loading
        loadingIndicator.classList.remove('hidden');
        errorMessage.classList.add('hidden');
        sheetsContainer.classList.add('hidden');
        fetchSheetsBtn.disabled = true;

        // Fetch sheets
        fetch('{{ route("admin.live-result-categories.fetch-sheets", $event->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                spreadsheet_id: spreadsheetId
            })
        })
        .then(response => response.json())
        .then(data => {
            loadingIndicator.classList.add('hidden');
            fetchSheetsBtn.disabled = false;

            if (data.success) {
                // Clear previous checkboxes
                sheetsCheckboxes.innerHTML = '';

                if (data.sheets && data.sheets.length > 0) {
                    // Create checkboxes
                    data.sheets.forEach(sheet => {
                        const label = document.createElement('label');
                        label.className = 'flex items-center gap-2 cursor-pointer';
                        
                        const checkbox = document.createElement('input');
                        checkbox.type = 'checkbox';
                        checkbox.name = 'selected_sheets[]';
                        checkbox.value = sheet;
                        checkbox.className = 'rounded border-gray-300 text-blue-600 focus:ring-blue-500';
                        
                        const span = document.createElement('span');
                        span.className = 'text-sm text-gray-700 dark:text-gray-300';
                        span.textContent = sheet;
                        
                        label.appendChild(checkbox);
                        label.appendChild(span);
                        sheetsCheckboxes.appendChild(label);
                    });

                    sheetsContainer.classList.remove('hidden');
                } else {
                    errorText.textContent = 'Tidak ada sheet ditemukan di spreadsheet ini.';
                    errorMessage.classList.remove('hidden');
                }
            } else {
                errorText.textContent = data.error || 'Gagal mengambil data dari Google Sheets.';
                errorMessage.classList.remove('hidden');
            }
        })
        .catch(error => {
            loadingIndicator.classList.add('hidden');
            fetchSheetsBtn.disabled = false;
            errorText.textContent = 'Terjadi kesalahan saat mengambil data.';
            errorMessage.classList.remove('hidden');
            console.error('Error:', error);
        });
    });

    // Print Modal Functions
    function openPrintModal(button) {
        const modal = document.getElementById('printModal');
        const form = document.getElementById('printForm');
        const roundSelect = document.getElementById('printRound');
        const modalTitle = document.getElementById('printModalTitle');
        
        // Get data from button attributes
        const categoryId = button.getAttribute('data-category-id');
        const sheetsData = button.getAttribute('data-sheets') || '[]';
        let selectedSheets = [];
        try {
            selectedSheets = JSON.parse(sheetsData);
        } catch (e) {
            console.error('Error parsing sheets data:', e);
            selectedSheets = [];
        }
        const categoryTitle = button.getAttribute('data-category-title');
        
        // Check if there are sheets available
        if (!selectedSheets || selectedSheets.length === 0) {
            alert('Tidak ada sheet yang dipilih untuk kategori ini. Silakan pilih sheet terlebih dahulu.');
            return;
        }
        
        // Set modal title
        modalTitle.textContent = `Print - ${categoryTitle}`;
        
        // Set form action
        form.action = '{{ route("admin.live-result-categories.print", [$event->id, ":categoryId"]) }}'.replace(':categoryId', categoryId);
        
        // Clear and populate round select
        roundSelect.innerHTML = '<option value="">-- Pilih Round --</option>';
        selectedSheets.forEach(function(sheet) {
            const option = document.createElement('option');
            option.value = sheet;
            option.textContent = sheet;
            roundSelect.appendChild(option);
        });
        
        // Show modal
        modal.classList.remove('hidden');
    }

    function closePrintModal() {
        const modal = document.getElementById('printModal');
        modal.classList.add('hidden');
    }

    // Close modal when clicking outside
    document.getElementById('printModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closePrintModal();
        }
    });
});
</script>
@endpush
@endsection
