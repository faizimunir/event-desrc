<x-layouts::app :title="__('Cetak Hasil') . ' — Print Center'">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Print Center') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <flux:heading>{{ __('Cetak Hasil') }}</flux:heading>
        <flux:subheading class="mb-6">{{ __('Pilih event untuk mencetak hasil live result (semua kategori pada round final).') }}</flux:subheading>

        @if (session('error'))
            <flux:callout variant="danger" class="rounded-lg mb-4">{{ session('error') }}</flux:callout>
        @endif

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 p-6 max-w-2xl">
            <form action="{{ route('print-center.preview') }}" method="GET" target="_blank" class="space-y-6">
                <div>
                    <flux:label class="mb-2 block">{{ __('Pilih Event') }} <span class="text-red-500">*</span></flux:label>
                    <flux:select name="event_id" required :placeholder="__('-- Pilih Event --')">
                        <flux:select.option value="">{{ __('-- Pilih Event --') }}</flux:select.option>
                        @foreach($events as $event)
                            @if($event->liveResultCategories->count() > 0)
                                <flux:select.option :value="$event->id">{{ $event->title }}</flux:select.option>
                            @endif
                        @endforeach
                    </flux:select>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Preview akan menampilkan semua kategori pada round final.') }}</p>
                </div>
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" icon="document-duplicate">{{ __('Buka Preview Cetak') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
