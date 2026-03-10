<div>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-2">
        <flux:input
            wire:model.live.debounce.500ms="search"
            type="search"
            :placeholder="__('Search by name…')"
            class="min-w-0 flex-1"
        />
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                        <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Roles') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                    @forelse ($this->permissions as $permission)
                        <tr
                            role="button"
                            tabindex="0"
                            class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                            onclick="window.location.href='{{ route('permissions.edit', $permission) }}'"
                            onkeydown="if (event.key === 'Enter') window.location.href='{{ route('permissions.edit', $permission) }}'"
                        >
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $permission->name }}</td>
                            <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $permission->roles_count }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No permissions found.') }}
                            </td>
                        </tr>
                    @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 flex flex-col items-center justify-between gap-2 sm:flex-row sm:gap-0">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ __('Showing :from–:to of :total', [
                'from' => $this->permissions->firstItem() ?? 0,
                'to' => $this->permissions->lastItem() ?? 0,
                'total' => $this->permissions->total(),
            ]) }}
        </p>
        @if ($this->permissions->hasPages())
            {{ $this->permissions->links() }}
        @endif
    </div>
</div>
