<div>
    <div class="users-hero-shell relative overflow-hidden bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 shadow-[0_12px_32px_-14px_rgba(249,115,22,0.55)] dark:from-orange-600 dark:via-orange-600 dark:to-amber-600 lg:-mx-4">
        <div class="pointer-events-none absolute -right-8 -top-8 size-32 rounded-full bg-white/10 blur-2xl" aria-hidden="true"></div>

        <div class="relative space-y-3 px-4 pb-3 pt-[max(0.5rem,env(safe-area-inset-top))] sm:px-5 sm:pb-4 lg:space-y-3.5 lg:pt-4">
            <div class="flex items-center gap-2.5 lg:hidden">
                <flux:sidebar.toggle
                    icon="bars-2"
                    inset="left"
                    class="!size-9 !rounded-xl !border !border-white/25 !bg-white/15 !text-white hover:!bg-white/25"
                />

                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs text-orange-100/80">
                        Admin
                    </p>
                    <h1 class="truncate text-sm font-semibold text-white">
                        {{ __('Users Management') }}
                    </h1>
                    
                </div>

                <flux:dropdown position="bottom" align="end">
                    <button
                        type="button"
                        class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-white/25 bg-white/15 text-xs font-semibold text-white transition hover:bg-white/25"
                        aria-label="{{ __('Account menu') }}"
                    >
                        {{ auth()->user()->initials() }}
                    </button>

                    @include('partials.mobile-user-menu')
                </flux:dropdown>
            </div>

            <div class="hidden items-center justify-between gap-3 lg:flex">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-orange-100/90">
                        {{ __('Admin') }}
                    </p>
                    <h1 class="truncate text-xl font-semibold tracking-tight text-white">
                        {{ __('Users Management') }}
                    </h1>
                </div>

                @canAs('user.create')
                    <flux:button
                        variant="primary"
                        size="sm"
                        :href="route('users.create')"
                        wire:navigate
                        icon="plus"
                        class="shrink-0 !border-0 !bg-white !text-orange-600 shadow-sm hover:!bg-orange-50"
                    >
                        {{ __('Add User') }}
                    </flux:button>
                @endcanAs
            </div>

            <div class="flex items-center gap-2">
                <flux:input
                    wire:model.live.debounce.500ms="search"
                    type="search"
                    :placeholder="__('Search…')"
                    class="min-w-0 flex-1"
                />

                <flux:dropdown position="bottom" align="end">
                    <flux:button
                        type="button"
                        icon="funnel"
                        square
                        class="users-hero-action shrink-0 {{ $roleFilter !== '' ? '!ring-2 !ring-white/50' : '' }}"
                        :aria-label="__('Filter by role')"
                    />

                    <flux:menu>
                        <flux:menu.item wire:click="setRoleFilter('')">
                            {{ __('All roles') }}
                        </flux:menu.item>

                        @foreach ($this->roles as $role)
                            <flux:menu.item wire:click="setRoleFilter('{{ $role->name }}')">
                                {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                            </flux:menu.item>
                        @endforeach
                    </flux:menu>
                </flux:dropdown>

                @canAs('user.update')
                    @canAs('user.delete')
                        <div class="relative shrink-0">
                            <flux:button
                                type="button"
                                variant="outline"
                                icon="arrows-pointing-in"
                                square
                                wire:click="openMergeModal"
                                :disabled="count($selectedUserIds) < 2"
                                class="users-hero-action"
                                :aria-label="__('Merge users')"
                            />
                            @if (count($selectedUserIds) > 0)
                                <span class="pointer-events-none absolute -right-1 -top-1 flex size-4 items-center justify-center rounded-full bg-white text-[10px] font-bold text-orange-600">
                                    {{ count($selectedUserIds) }}
                                </span>
                            @endif
                        </div>
                    @endcanAs
                @endcanAs

                @canAs('user.create')
                    <flux:button
                        variant="primary"
                        size="sm"
                        :href="route('users.create')"
                        wire:navigate
                        icon="plus"
                        square
                        class="users-hero-action shrink-0 !border-0 !bg-white !text-orange-600 hover:!bg-orange-50 lg:hidden"
                        :aria-label="__('Add User')"
                    />
                @endcanAs
            </div>
        </div>
    </div>

    <div class="users-hero-content">
        @error('merge')
            <flux:callout variant="danger" class="mb-3 mt-3">{{ $message }}</flux:callout>
        @enderror

        <div class="flex items-center justify-between gap-3 py-3">
            <div class="min-w-0">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ __('All users') }}
                </h2>
                @if ($roleFilter !== '')
                    <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Role') }}: {{ ucfirst(str_replace('_', ' ', $roleFilter)) }}
                    </p>
                @endif
            </div>

            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->users->total()) }}
            </span>
        </div>

        @if ($this->users->isNotEmpty())
            <div class="users-list-panel" wire:key="users-paged-p{{ $this->users->currentPage() }}">
                @foreach ($this->users as $user)
                    @php
                        $primaryRole = $user->roles->first()?->name;
                    @endphp

                    <div wire:key="user-{{ $user->id }}" class="users-list-row group">
                        @canAs('user.update')
                            @canAs('user.delete')
                                <div class="shrink-0" onclick="event.stopPropagation()" onkeydown="event.stopPropagation()">
                                    <flux:checkbox wire:model.live="selectedUserIds" :value="$user->id" />
                                </div>
                            @endcanAs
                        @endcanAs

                        @canAs('user.update')
                            @can('update', $user)
                                <a
                                    href="{{ route('users.edit', $user) }}"
                                    wire:navigate
                                    class="flex min-w-0 flex-1 items-center gap-2.5"
                                >
                            @else
                                <div class="flex min-w-0 flex-1 items-center gap-2.5">
                            @endcan
                        @else
                            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                        @endcanAs
                            <div class="users-list-avatar">
                                {{ $user->initials() }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                        {{ $user->name }}
                                    </p>
                                    @if ($primaryRole)
                                        <span class="hidden shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 sm:inline dark:bg-zinc-700 dark:text-zinc-400">
                                            {{ $primaryRole }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    @if ($user->whatsapp)
                                        <span class="inline-flex items-center gap-1">
                                            <flux:icon name="chat-bubble-left-right" class="size-3 shrink-0" />
                                            {{ $user->whatsapp }}
                                        </span>
                                    @elseif ($user->email)
                                        <span class="inline-flex items-center gap-1">
                                            <flux:icon name="envelope" class="size-3 shrink-0" />
                                            {{ $user->email }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>

                            <div class="flex shrink-0 items-center gap-1.5">
                                @if ($primaryRole)
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 sm:hidden dark:bg-zinc-700 dark:text-zinc-400">
                                        {{ $primaryRole }}
                                    </span>
                                @endif

                                @canAs('user.update')
                                    @can('update', $user)
                                        <flux:icon
                                            name="chevron-right"
                                            variant="mini"
                                            class="size-4 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                                        />
                                    @endcan
                                @endcanAs
                            </div>
                        @canAs('user.update')
                            @can('update', $user)
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
                    <flux:icon name="users" class="size-5 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No users found.') }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or filters.') }}</p>
            </div>
        @endif

        @if ($this->users->hasPages())
            <div class="mt-4 pb-2">
                {{ $this->users->links() }}
            </div>
        @endif
    </div>

    @canAs('user.update')
        @canAs('user.delete')
            <flux:modal
                name="merge-users-modal"
                class="max-w-2xl"
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
                        <flux:field :label="__('Primary account')">
                            <div class="space-y-2">
                                @foreach ($mergeCandidates as $c)
                                    <label
                                        class="flex cursor-pointer gap-3 rounded-xl border border-zinc-200 bg-white p-4 has-[:checked]:border-zinc-400 has-[:checked]:ring-2 has-[:checked]:ring-zinc-400/30 dark:border-zinc-600 dark:bg-zinc-800/50 dark:has-[:checked]:border-zinc-400 dark:has-[:checked]:ring-zinc-400/20"
                                    >
                                        <input
                                            type="radio"
                                            wire:model.live="mergePrimaryUserId"
                                            value="{{ $c['id'] }}"
                                            class="mt-1 size-4 shrink-0 border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-500 dark:bg-zinc-800 dark:focus:ring-zinc-400"
                                        />
                                        <div class="min-w-0 flex-1 space-y-1 text-sm">
                                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $c['name'] }}</div>
                                            <div class="text-zinc-500 dark:text-zinc-400">
                                                <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ __('ID') }}:</span>
                                                {{ $c['id'] }}
                                            </div>
                                            <div class="text-zinc-500 dark:text-zinc-400">
                                                <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ __('Email') }}:</span>
                                                {{ $c['email'] ?? '—' }}
                                            </div>
                                            <div class="text-zinc-500 dark:text-zinc-400">
                                                <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ __('WhatsApp') }}:</span>
                                                {{ $c['whatsapp'] ?? '—' }}
                                            </div>
                                            <div class="break-words text-zinc-500 dark:text-zinc-400">
                                                <span class="font-medium text-zinc-600 dark:text-zinc-300">{{ __('Linked riders') }}:</span>
                                                {{ $c['riders_display'] }}
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </flux:field>
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
