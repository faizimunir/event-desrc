<x-layouts::app :title="__('Add Organizer')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('organizers.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Add Organizer') }}</flux:heading>

        <form method="post" action="{{ route('organizers.store') }}" class="max-w-lg space-y-6">
            @csrf

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name')" required autofocus />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="link" type="url" :label="__('Link')" :value="old('link')" placeholder="https://..." />
            @error('link')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <flux:button variant="primary" type="submit">{{ __('Create Organizer') }}</flux:button>
                <flux:button variant="ghost" :href="route('organizers.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
