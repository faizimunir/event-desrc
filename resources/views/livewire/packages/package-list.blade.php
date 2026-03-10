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
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Price') }}</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Quota') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse ($this->packages as $package)
                    @canAs('package.update')
                        @can('update', $package)
                            <tr
                                role="button"
                                tabindex="0"
                                class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                                onclick="window.location.href='{{ route('events.packages.edit', [$event, $package]) }}'"
                                onkeydown="if (event.key === 'Enter') window.location.href='{{ route('events.packages.edit', [$event, $package]) }}'"
                            >
                        @else
                            <tr>
                        @endcan
                    @else
                        <tr>
                    @endcanAs
                        <td class="px-4 py-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $package->name }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $package->formatted_price }}</td>
                        <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">
                            @if ($package->quota !== null)
                                @php $rem = $package->remainingQuota(); @endphp
                                {{ $rem !== null ? $rem . ' / ' . $package->quota : $package->quota }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No packages found.') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($this->packages->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $this->packages->links() }}
        </div>
    @endif
</div>
