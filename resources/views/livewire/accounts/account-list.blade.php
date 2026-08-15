<div>
    <x-admin-hero-header
        :heading="__('Accounts Management')"
        create-permission="account.create"
        :create-href="route('accounts.create')"
        :create-label="__('Add Account')"
    >
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name, bank or number…')"
            class="min-w-0 flex-1"
        />
    </x-admin-hero-header>

    <div class="users-hero-content pb-6">
        <div class="flex items-center justify-between gap-3 py-3">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('All accounts') }}</h2>
            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->accounts->total()) }}
            </span>
        </div>

        @if ($this->accounts->isNotEmpty())
            <div class="users-list-panel" wire:key="accounts-paged-p{{ $this->accounts->currentPage() }}">
                @foreach ($this->accounts as $account)
                    <div wire:key="account-{{ $account->id }}" class="users-list-row group">
                        @canAs('account.update')
                            @can('update', $account)
                                <a href="{{ route('accounts.edit', $account) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-2.5">
                            @else
                                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                            @endcan
                        @else
                            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                        @endcanAs
                            <div class="users-list-avatar">
                                <flux:icon name="building-library" class="size-4" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                    {{ $account->acc_name }}
                                </p>
                                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ collect([$account->acc_bank, $account->acc_number])->filter()->implode(' · ') }}
                                </p>
                            </div>
                            @canAs('account.update')
                                @can('update', $account)
                                    <flux:icon name="chevron-right" variant="mini" class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400" />
                                @endcan
                            @endcanAs
                        @canAs('account.update')
                            @can('update', $account)
                                </a>
                            @else
                                </div>
                            @endcan
                        @else
                            </div>
                        @endcanAs
                    </div>
                @endforeach
            </div>
        @else
            <div class="users-list-panel px-4 py-12 text-center">
                <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="building-library" class="size-5 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No accounts found.') }}</p>
            </div>
        @endif

        @if ($this->accounts->hasPages())
            <div class="mt-4 pb-2">{{ $this->accounts->links() }}</div>
        @endif
    </div>
</div>
