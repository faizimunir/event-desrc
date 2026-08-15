<x-layouts::app :title="__('Edit Account')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Edit Account')"
            :subheading="__('Accounts')"
            :back-href="route('accounts.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
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
                    <form id="delete-account-form-{{ $account->id }}" method="post" action="{{ route('accounts.destroy', $account) }}" class="mt-2">
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
    </div>
</x-layouts::app>
