<x-layouts::app :title="__('Add Role')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Add Role')"
            :subheading="__('Roles')"
            :back-href="route('roles.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <form method="post" action="{{ route('roles.store') }}" class="max-w-lg space-y-6">
                @csrf

                <flux:input name="name" type="text" :label="__('Name')" :value="old('name')" required autofocus placeholder="e.g. editor" />
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <div>
                    <flux:label class="mb-2 block">{{ __('Permissions') }}</flux:label>
                    <div class="flex max-h-64 flex-wrap gap-x-4 gap-y-2 overflow-y-auto rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        @foreach ($permissions as $permission)
                            <label class="inline-flex items-center gap-2">
                                <flux:checkbox name="permissions[]" :value="$permission->name" :checked="in_array($permission->name, old('permissions', []))" />
                                <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $permission->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @if ($permissions->isEmpty())
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No permissions yet. Create permissions first.') }}</p>
                    @endif
                    @error('permissions')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-2">
                    <flux:button variant="primary" type="submit">{{ __('Create Role') }}</flux:button>
                    <flux:button variant="ghost" :href="route('roles.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
