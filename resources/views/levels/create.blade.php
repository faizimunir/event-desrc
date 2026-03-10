<x-layouts::app :title="__('Add Level')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('levels.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Add Level') }}</flux:heading>

        <form method="post" action="{{ route('levels.store') }}" class="max-w-lg space-y-6">
            @csrf

            <flux:input name="code" type="text" :label="__('Code')" :value="old('code')" required autofocus />
            @error('code')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name')" required />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="order" type="number" :label="__('Order')" :value="old('order', 0)" min="0" required />
            @error('order')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <flux:button variant="primary" type="submit">{{ __('Create Level') }}</flux:button>
                <flux:button variant="ghost" :href="route('levels.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
