<x-layouts::app :title="__('Add Reward')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('rewards.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Add Reward') }}</flux:heading>

        <form method="post" action="{{ route('rewards.store') }}" class="max-w-lg space-y-6">
            @csrf

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name')" required autofocus />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="icon" type="text" :label="__('Icon')" :value="old('icon')" placeholder="e.g. trophy, award, or URL" />
            @error('icon')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <flux:button variant="primary" type="submit">{{ __('Create Reward') }}</flux:button>
                <flux:button variant="ghost" :href="route('rewards.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
