<x-layouts::app :title="__('Kelola Live Result') . ' — ' . $event->title">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', ['event' => $event, 'tab' => 'live-result'])" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Kelola Live Result') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', ['event' => $event, 'tab' => 'live-result'])" wire:navigate icon="arrow-left">
                {{ __('Back to event') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Kelola Live Result') }}</flux:heading>
        <flux:subheading class="mb-6">{{ __('Kelola kategori dan round live result dari Google Sheets untuk event ini.') }}</flux:subheading>

        @include('admin.live-result-categories.partials.manage', ['event' => $event, 'categories' => $categories])
    </div>
</x-layouts::app>
