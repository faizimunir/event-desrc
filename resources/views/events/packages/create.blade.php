<x-layouts::app :title="__('Add Package')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.packages.index', $event)" wire:navigate>{{ __('Packages') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Add Package') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.packages.index', $event)" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Add Package') }}</flux:heading>

        <livewire:packages.package-form :event="$event" />
    </div>
</x-layouts::app>
