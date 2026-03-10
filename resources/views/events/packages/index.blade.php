<x-layouts::app :title="__('Packages')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Packages') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" :href="route('events.show', $event)" wire:navigate icon="arrow-left">
                    {{ __('Back') }}
                </flux:button>
            </div>
            @canAs('package.create')
                <flux:button variant="primary" :href="route('events.packages.create', $event)" wire:navigate icon="plus">
                    {{ __('Add Package') }}
                </flux:button>
            @endcanAs
        </div>

        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Packages define registration price and race pack. If there is only one package, participants will not need to choose.') }}
        </p>

        <livewire:packages.package-list :event="$event" />
    </div>
</x-layouts::app>
