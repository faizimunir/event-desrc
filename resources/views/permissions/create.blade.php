<x-layouts::app :title="__('Add Permission')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Add Permission')"
            :subheading="__('Permissions')"
            :back-href="route('permissions.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <form method="post" action="{{ route('permissions.store') }}" class="max-w-lg space-y-6">
                @csrf

                <flux:input name="name" type="text" :label="__('Name')" :value="old('name')" required autofocus placeholder="e.g. update.rider" />
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <div class="flex gap-2">
                    <flux:button variant="primary" type="submit">{{ __('Create Permission') }}</flux:button>
                    <flux:button variant="ghost" :href="route('permissions.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
