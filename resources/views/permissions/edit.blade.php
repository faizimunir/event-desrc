<x-layouts::app :title="__('Edit Permission')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Edit Permission')"
            :subheading="__('Permissions')"
            :back-href="route('permissions.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
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

            <form method="post" action="{{ route('permissions.destroy', $permission) }}" class="mt-2" onsubmit="return confirm('{{ addslashes(__('Are you sure you want to delete this permission?')) }}');">
                @csrf
                @method('DELETE')
                <flux:button type="submit" variant="danger" icon="trash">
                    {{ __('Delete Permission') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::app>
