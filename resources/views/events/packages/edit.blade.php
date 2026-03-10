<x-layouts::app :title="__('Edit Package')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.packages.index', $event)" wire:navigate>{{ __('Packages') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $package->name }} — {{ __('Edit') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.packages.index', $event)" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Edit Package') }}</flux:heading>

        <livewire:packages.package-form :event="$event" :package="$package" />

        @canAs('package.delete')
            @can('delete', $package)
                <form id="delete-package-form-{{ $package->id }}" method="post" action="{{ route('events.packages.destroy', [$event, $package]) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this package?')) }}')) document.getElementById('delete-package-form-{{ $package->id }}').submit()"
                    >
                        {{ __('Delete Package') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>
</x-layouts::app>
