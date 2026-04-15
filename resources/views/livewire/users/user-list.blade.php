<div>
    @error('merge')
        <flux:callout variant="danger" class="mb-3">{{ $message }}</flux:callout>
    @enderror

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
        @canAs('user.update')
            @canAs('user.delete')
                <flux:button
                    type="button"
                    variant="outline"
                    icon="arrows-pointing-in"
                    wire:click="openMergeModal"
                    :disabled="count($selectedUserIds) < 2"
                >
                    {{ __('Merge users') }}
                </flux:button>
            @endcanAs
        @endcanAs
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    @canAs('user.update')
                        @canAs('user.delete')
                            <th scope="col" class="w-12 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <span class="sr-only">{{ __('Select for merge') }}</span>
                            </th>
                        @endcanAs
                    @endcanAs
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
                                wire:key="user-row-{{ $user->id }}"
                                role="button"
                                tabindex="0"
                                class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                                onclick="window.location.href='{{ route('users.edit', $user) }}'"
                                onkeydown="if (event.key === 'Enter') window.location.href='{{ route('users.edit', $user) }}'"
                            >
                        @else
                            <tr wire:key="user-row-{{ $user->id }}">
                        @endcan
                    @else
                        <tr wire:key="user-row-{{ $user->id }}">
                    @endcanAs
                        @canAs('user.update')
                            @canAs('user.delete')
                                <td class="px-4 py-3" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                                    <flux:checkbox wire:model.live="selectedUserIds" :value="$user->id" />
                                </td>
                            @endcanAs
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
                        <td colspan="{{ auth()->user()->canAs('user.update') && auth()->user()->canAs('user.delete') ? 5 : 4 }}" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
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

    @canAs('user.update')
        @canAs('user.delete')
            <flux:modal
                name="merge-users-modal"
                class="max-w-lg"
                wire:model="mergeModalOpen"
                @close="closeMergeModal"
                focusable
                dismissible
            >
                <form wire:submit="confirmMerge" class="space-y-4">
                    <flux:heading size="lg">{{ __('Merge users') }}</flux:heading>
                    <flux:text>
                        {{ __('Choose the account to keep. Related data (riders, orders, organizers, check-ins, payment reviews, sessions) from other selected accounts will point to this user, roles will be combined, and duplicate accounts will be removed.') }}
                    </flux:text>
                    @if (count($mergeCandidates) > 0)
                        <flux:radio.group wire:model.live="mergePrimaryUserId" :label="__('Primary account')" variant="cards" class="max-sm:flex-col">
                            @foreach ($mergeCandidates as $c)
                                <flux:radio :value="$c['id']" :label="$c['name']" :description="__('ID :id', ['id' => $c['id']])" />
                            @endforeach
                        </flux:radio.group>
                    @endif
                    @error('merge')
                        <flux:callout variant="danger" size="sm">{{ $message }}</flux:callout>
                    @enderror
                    <div class="flex justify-end gap-2">
                        <flux:modal.close>
                            <flux:button type="button" variant="filled">{{ __('Cancel') }}</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="danger" icon="arrows-pointing-in">
                            {{ __('Confirm merge') }}
                        </flux:button>
                    </div>
                </form>
            </flux:modal>
        @endcanAs
    @endcanAs
</div>
