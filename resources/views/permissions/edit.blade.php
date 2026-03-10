<x-layouts::app :title="__('Edit Permission')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('permissions.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Edit Permission') }}</flux:heading>

        <form method="post" action="{{ route('permissions.update', $permission) }}" class="max-w-lg space-y-6">
            @csrf
            @method('PUT')

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name', $permission->name)" required autofocus />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" type="submit">{{ __('Update Permission') }}</flux:button>
                <flux:button variant="ghost" :href="route('permissions.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>

        <form method="post" action="{{ route('permissions.destroy', $permission) }}" class="mt-6" onsubmit="return confirm('{{ addslashes(__('Are you sure you want to delete this permission?')) }}');">
            @csrf
            @method('DELETE')
            <flux:button type="submit" variant="danger" icon="trash">
                {{ __('Delete Permission') }}
            </flux:button>
        </form>
    </div>
</x-layouts::app>
