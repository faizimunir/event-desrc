<x-layouts::app :title="__('Add Reward')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Add Reward')"
            :subheading="__('Rewards')"
            :back-href="route('rewards.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
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
    </div>
</x-layouts::app>
