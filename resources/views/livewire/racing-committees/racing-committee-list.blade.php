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
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Photo') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Name') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Link') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse ($this->racingCommittees as $rc)
                    @canAs('rc.update')
                        @can('update', $rc)
                            <tr
                                role="button"
                                tabindex="0"
                                class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                                onclick="window.location.href='{{ route('racing-committees.edit', $rc) }}'"
                                onkeydown="if (event.key === 'Enter') window.location.href='{{ route('racing-committees.edit', $rc) }}'"
                            >
                        @else
                            <tr>
                        @endcan
                    @else
                        <tr>
                    @endcanAs
                        <td class="px-4 py-3">
                            @if ($rc->photo_rc_url)
                                <flux:avatar :src="$rc->photo_rc_url" :alt="$rc->name" class="h-10 w-10 rounded-full object-cover" />
                            @else
                                <flux:avatar :name="$rc->name" :initials="$rc->initials()" class="h-10 w-10" />
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $rc->name }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400" title="{{ $rc->link }}">
                            @if ($rc->link)
                                <a href="{{ $rc->link }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline" onclick="event.stopPropagation()">
                                    {{ \Illuminate\Support\Str::limit($rc->link, 50) }}
                                </a>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No racing committees found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->racingCommittees->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $this->racingCommittees->links() }}
        </div>
    @endif
</div>
