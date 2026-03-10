<x-layouts::app :title="__('Edit Role')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('roles.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Edit Role') }}</flux:heading>

        <form method="post" action="{{ route('roles.update', $role) }}" class="max-w-lg space-y-6">
            @csrf
            @method('PUT')

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name', $role->name)" required autofocus />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div>
                <flux:label class="mb-2 block">{{ __('Permissions') }}</flux:label>
                <div class="flex max-h-64 flex-wrap gap-x-4 gap-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                    @foreach ($permissions as $permission)
                        <label class="inline-flex items-center gap-2">
                            <flux:checkbox name="permissions[]" :value="$permission->name" :checked="in_array($permission->name, old('permissions', $role->permissions->pluck('name')->all()))" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $permission->name }}</span>
                        </label>
                    @endforeach
                </div>
                @error('permissions')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" type="submit">{{ __('Update Role') }}</flux:button>
                <flux:button variant="ghost" :href="route('roles.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>

        @if ($role->name !== 'super_admin')
            <form method="post" action="{{ route('roles.destroy', $role) }}" class="mt-6" onsubmit="return confirm('{{ addslashes(__('Are you sure you want to delete this role?')) }}');">
                @csrf
                @method('DELETE')
                <flux:button type="submit" variant="danger" icon="trash">
                    {{ __('Delete Role') }}
                </flux:button>
            </form>
        @endif
    </div>
</x-layouts::app>
