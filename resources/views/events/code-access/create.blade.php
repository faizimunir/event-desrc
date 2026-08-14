<x-layouts::app :title="__('Add code')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'code-access'])" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Add code') }}</flux:heading>
        <flux:subheading>{{ __('Share these codes to allow early registration before registration opens.') }}</flux:subheading>

        <form method="POST" action="{{ route('events.code-access.store', $event) }}" class="max-w-lg space-y-4">
            @csrf
            <flux:input name="code" type="text" :label="__('Code')" :value="old('code')" :placeholder="__('e.g. EARLY2025')" required autofocus />
            @error('code')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="name" type="text" :label="__('Name (optional)')" :value="old('name')" :placeholder="__('e.g. Early Bird')" />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input name="valid_from" type="datetime-local" :label="__('Valid from (optional)')" :value="old('valid_from')" />
                <flux:input name="valid_until" type="datetime-local" :label="__('Valid until (optional)')" :value="old('valid_until')" />
            </div>
            @error('valid_from')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            @error('valid_until')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="usage_limit" type="number" min="1" :label="__('Usage limit (optional)')" :value="old('usage_limit')" :placeholder="__('Max uses, leave empty for unlimited')" />
            @error('usage_limit')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('Add code') }}</flux:button>
                <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'code-access'])" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
