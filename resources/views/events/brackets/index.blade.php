<x-layouts::app :title="__('Brackets')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Brackets') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" :href="route('events.show', $event)" wire:navigate icon="arrow-left">
                    {{ __('Back') }}
                </flux:button>
            </div>
            @canAs('bracket.create')
                <flux:button variant="primary" :href="route('events.brackets.create', $event)" wire:navigate icon="plus">
                    {{ __('Add Bracket') }}
                </flux:button>
            @endcanAs
        </div>

        <livewire:brackets.bracket-list :event="$event" />
    </div>
</x-layouts::app>
