<x-layouts::app :title="__('Edit code')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'code-access'])" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Edit code') }}</flux:heading>

        <form method="POST" action="{{ route('events.code-access.update', [$event, $codeAccess]) }}" class="max-w-lg space-y-4">
            @csrf
            @method('PUT')
            <flux:input name="code" type="text" :label="__('Code')" :value="old('code', $codeAccess->code)" :placeholder="__('e.g. EARLY2025')" required autofocus />
            @error('code')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="name" type="text" :label="__('Name (optional)')" :value="old('name', $codeAccess->name)" :placeholder="__('e.g. Early Bird')" />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input name="valid_from" type="datetime-local" :label="__('Valid from (optional)')" :value="old('valid_from', $codeAccess->valid_from?->format('Y-m-d\\TH:i'))" />
                <flux:input name="valid_until" type="datetime-local" :label="__('Valid until (optional)')" :value="old('valid_until', $codeAccess->valid_until?->format('Y-m-d\\TH:i'))" />
            </div>
            @error('valid_from')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            @error('valid_until')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="usage_limit" type="number" min="1" :label="__('Usage limit (optional)')" :value="old('usage_limit', $codeAccess->usage_limit)" :placeholder="__('Max uses, leave empty for unlimited')" />
            @error('usage_limit')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('Update code') }}</flux:button>
                <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'code-access'])" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>

        <form id="delete-code-access-form-{{ $codeAccess->id }}" method="POST" action="{{ route('events.code-access.destroy', [$event, $codeAccess]) }}" class="mt-6">
            @csrf
            @method('DELETE')
            <flux:button
                type="button"
                variant="danger"
                icon="trash"
                onclick="if(confirm({{ json_encode(__('Remove this code?')) }})) document.getElementById('delete-code-access-form-{{ $codeAccess->id }}').submit()"
            >
                {{ __('Remove') }}
            </flux:button>
        </form>
    </div>
</x-layouts::app>
