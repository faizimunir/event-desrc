<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-2">
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name, email, WhatsApp…')"
            class="min-w-0 flex-1"
        />
        <flux:select wire:model.live="roleFilter" :placeholder="__('All roles')" class="w-full sm:w-48">
            @foreach ($this->roles as $role)
                <flux:select.option :value="$role->name">{{ ucfirst(str_replace('_', ' ', $role->name)) }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('WhatsApp') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Roles') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse ($this->users as $user)
                    @canAs('user.update')
                        @can('update', $user)
                            <tr
                                role="button"
                                tabindex="0"
                                class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                                onclick="window.location.href='{{ route('users.edit', $user) }}'"
                                onkeydown="if (event.key === 'Enter') window.location.href='{{ route('users.edit', $user) }}'"
                            >
                        @else
                            <tr>
                        @endcan
                    @else
                        <tr>
                    @endcanAs
                        <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $user->name }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $user->whatsapp ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            @foreach ($user->roles as $role)
                                <flux:badge color="zinc" class="me-1" size="sm">{{ $role->name }}</flux:badge>
                            @endforeach
                            @if ($user->roles->isEmpty())
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No users found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->users->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $this->users->links() }}
        </div>
    @endif
</div>
