<x-layouts::app :title="__('Edit Account')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('accounts.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Edit Account') }}</flux:heading>

        <form method="post" action="{{ route('accounts.update', $account) }}" class="max-w-lg space-y-6">
            @csrf
            @method('PUT')

            <flux:input name="acc_name" type="text" :label="__('Account Name')" :value="old('acc_name', $account->acc_name)" required autofocus />
            @error('acc_name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="acc_bank" type="text" :label="__('Bank')" :value="old('acc_bank', $account->acc_bank)" required />
            @error('acc_bank')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="acc_number" type="text" :label="__('Account Number')" :value="old('acc_number', $account->acc_number)" required />
            @error('acc_number')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" type="submit">{{ __('Update Account') }}</flux:button>
                <flux:button variant="ghost" :href="route('accounts.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
        @canAs('account.delete')
            @can('delete', $account)
                <form id="delete-account-form-{{ $account->id }}" method="post" action="{{ route('accounts.destroy', $account) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this account?')) }}')) document.getElementById('delete-account-form-{{ $account->id }}').submit()"
                    >
                        {{ __('Delete Account') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>
</x-layouts::app>
