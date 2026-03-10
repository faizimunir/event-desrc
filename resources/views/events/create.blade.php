<x-layouts::app :title="__('Add Event')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Add Event') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <livewire:events.event-form />
    </div>
</x-layouts::app>
