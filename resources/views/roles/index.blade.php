<x-layouts::app :title="__('Roles')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-hero-header
            :heading="__('Roles')"
            :create-href="route('roles.create')"
            :create-label="__('Add Role')"
        />

        <div class="users-hero-content pb-6">
            <div class="flex items-center justify-between gap-3 py-3">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('All roles') }}</h2>
                <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                    {{ number_format($roles->total()) }}
                </span>
            </div>

            @if ($roles->isNotEmpty())
                <div class="users-list-panel">
                    @foreach ($roles as $role)
                        <div class="users-list-row group">
                            <a
                                href="{{ route('roles.edit', $role) }}"
                                wire:navigate
                                class="flex min-w-0 flex-1 items-center gap-2.5"
                            >
                                <div class="users-list-avatar">
                                    <flux:icon name="shield-check" class="size-4" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                        {{ $role->name }}
                                    </p>
                                    <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Permissions') }}: {{ $role->permissions_count }}
                                    </p>
                                </div>
                                <flux:icon
                                    name="chevron-right"
                                    variant="mini"
                                    class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                                />
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="users-list-panel px-4 py-12 text-center">
                    <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon name="shield-check" class="size-5 text-zinc-400" />
                    </div>
                    <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('No roles found.') }}</p>
                </div>
            @endif

            @if ($roles->hasPages())
                <div class="mt-4 pb-2">
                    {{ $roles->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layouts::app>
