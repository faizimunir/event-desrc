<x-layouts::app :title="__('Add Level')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Add Level')"
            :subheading="__('Levels')"
            :back-href="route('levels.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
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
    </div>
</x-layouts::app>
