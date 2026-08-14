<x-layouts::app :title="__('Tambah Kategori')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'live-result'])" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Tambah Kategori') }}</flux:heading>
        <flux:subheading>{{ __('Kelola kategori dan round live result dari Google Sheets untuk event ini.') }}</flux:subheading>

        @include('admin.live-result-categories.partials.form', ['event' => $event])
    </div>
</x-layouts::app>
