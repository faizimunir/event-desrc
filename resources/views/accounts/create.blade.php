<x-layouts::app :title="__('Add Account')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('accounts.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Add Account') }}</flux:heading>

        <form method="post" action="{{ route('accounts.store') }}" class="max-w-lg space-y-6">
            @csrf

            <flux:input name="acc_name" type="text" :label="__('Account Name')" :value="old('acc_name')" required autofocus />
            @error('acc_name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="acc_bank" type="text" :label="__('Bank')" :value="old('acc_bank')" required />
            @error('acc_bank')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="acc_number" type="text" :label="__('Account Number')" :value="old('acc_number')" required />
            @error('acc_number')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <flux:button variant="primary" type="submit">{{ __('Create Account') }}</flux:button>
                <flux:button variant="ghost" :href="route('accounts.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
    </div>
</x-layouts::app>
