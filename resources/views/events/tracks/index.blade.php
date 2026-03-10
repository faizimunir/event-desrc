<x-layouts::app :title="__('Tracks')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Tracks') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" :href="route('events.show', $event)" wire:navigate icon="arrow-left">
                    {{ __('Back') }}
                </flux:button>
            </div>
            @canAs('track.create')
                <flux:button variant="primary" :href="route('events.tracks.create', $event)" wire:navigate icon="plus">
                    {{ __('Add Track') }}
                </flux:button>
            @endcanAs
        </div>

        @if (session('status'))
            <flux:callout variant="success" class="rounded-lg">{{ session('status') }}</flux:callout>
        @endif

        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Tracks define the race circuit or route for this event (name, material, length, photo).') }}
        </p>

        <livewire:tracks.track-list :event="$event" />
    </div>
</x-layouts::app>
