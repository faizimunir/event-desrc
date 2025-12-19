@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.hook('morph.updated', ({ el, component }) => {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.display = 'none';
            }
        });
        
        document.addEventListener('livewire:navigating', () => {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.display = 'flex';
            }
        });
        
        document.addEventListener('livewire:navigated', () => {
            const loader = document.getElementById('page-loader');
            if (loader) {
                loader.style.display = 'none';
            }
        });
    });
</script>
@endpush

<div 
    id="page-loader"
    class="fixed inset-0 z-50 flex items-center justify-center bg-white dark:bg-gray-900 bg-opacity-90 dark:bg-opacity-90"
    style="display: none;"
>
    <div class="text-center">
        <!-- Spinner -->
        <div class="inline-block animate-spin rounded-full h-16 w-16 border-t-4 border-b-4 border-blue-600 dark:border-blue-400 mb-4"></div>
        <p class="text-gray-700 dark:text-gray-300 text-lg font-medium">Memuat...</p>
    </div>
</div>

