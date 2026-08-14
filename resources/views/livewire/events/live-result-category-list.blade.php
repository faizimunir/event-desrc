<div>
    @if (! $event->has_live_result)
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('Live Result') }}
            </h2>
            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                0
            </span>
        </div>

        <div class="users-list-panel px-4 py-12 text-center">
            <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                <flux:icon name="chart-bar" class="size-5 text-zinc-400" />
            </div>
            <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">
                {{ __('Fitur Live Result belum diaktifkan.') }}
            </p>
            <p class="mx-auto mt-1 max-w-md text-xs text-zinc-500 dark:text-zinc-400">
                {{ __('Aktifkan terlebih dahulu di halaman Edit Event untuk mengelola kategori dan menampilkan di halaman publik.') }}
            </p>
            @canAs('event.update')
                <div class="mt-4">
                    <flux:button variant="primary" size="sm" :href="route('events.edit', $event)" wire:navigate>
                        {{ __('Edit Event') }}
                    </flux:button>
                </div>
            @endcanAs
        </div>
    @else
        <div class="mb-4 flex flex-row flex-wrap items-center gap-2">
            @canAs('manage_live_results')
                <flux:button
                    variant="primary"
                    :href="route('events.live-result-categories.create', $event)"
                    wire:navigate
                    icon="plus"
                    square
                    class="shrink-0"
                    :aria-label="__('Tambah Kategori')"
                />
            @endcanAs

            <flux:input
                wire:model.live.debounce.500ms="search"
                type="search"
                :placeholder="__('Search by name…')"
                class="min-w-0 flex-1"
            />

            @if ($this->categories->total() > 0)
                @canAs('manage_live_results')
                    <flux:button
                        type="button"
                        variant="outline"
                        square
                        class="shrink-0"
                        wire:click="syncAll"
                        wire:loading.attr="disabled"
                        wire:target="syncAll"
                        :aria-label="__('Sync All')"
                    >
                        <span wire:loading.remove wire:target="syncAll" class="inline-flex">
                            <flux:icon :name="$justSyncedAll ? 'check' : 'arrow-path'" variant="mini" class="size-4" />
                        </span>
                        <span wire:loading wire:target="syncAll" class="inline-flex">
                            <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin" />
                        </span>
                    </flux:button>
                @endcanAs

                <flux:button
                    variant="outline"
                    :href="route('print-center.index')"
                    wire:navigate
                    icon="printer"
                    square
                    class="shrink-0"
                    :aria-label="__('Print Center')"
                />
            @endif
        </div>

        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                {{ __('Live Result') }}
            </h2>
            <span class="shrink-0 rounded-full bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-600 dark:bg-orange-500/15 dark:text-orange-400">
                {{ number_format($this->categories->total()) }}
            </span>
        </div>

        @if ($this->categories->isEmpty())
            <div class="users-list-panel px-4 py-12 text-center">
                <div class="mx-auto flex size-11 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="chart-bar" class="size-5 text-zinc-400" />
                </div>
                <p class="mt-3 text-sm font-medium text-zinc-600 dark:text-zinc-300">{{ __('Belum ada kategori.') }}</p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting your search or add a new category.') }}</p>
            </div>
        @else
            <div class="users-list-panel" wire:key="live-result-paged-p{{ $this->categories->currentPage() }}">
                @foreach ($this->categories as $category)
                    @php
                        $canUpdate = auth()->user()->canAs('manage_live_results') && auth()->user()->can('update', $event);
                        $sheetCount = is_array($category->selected_sheets) ? count($category->selected_sheets) : 0;
                        $metaParts = array_values(array_filter([
                            $sheetCount > 0 ? $sheetCount.' '.__('sheets') : __('Belum dipilih'),
                            $category->last_sync ? $category->last_sync->format('d M Y H:i') : __('Belum pernah'),
                            \Illuminate\Support\Str::limit($category->spreadsheet_id, 22),
                        ]));
                    @endphp

                    <div wire:key="live-result-category-{{ $category->id }}" class="users-list-row group">
                        @if ($canUpdate)
                            <a
                                href="{{ route('events.live-result-categories.edit', [$event, $category]) }}"
                                wire:navigate
                                class="flex min-w-0 flex-1 items-center gap-2.5"
                            >
                        @else
                            <div class="flex min-w-0 flex-1 items-center gap-2.5">
                        @endif
                            <div class="users-list-avatar">
                                <flux:icon name="chart-bar" variant="outline" class="size-4" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="truncate text-sm font-medium text-zinc-900 transition group-hover:text-orange-600 dark:text-zinc-100 dark:group-hover:text-orange-400">
                                        {{ $category->title }}
                                    </p>
                                    @if ($category->is_active)
                                        <span class="hidden shrink-0 rounded-full bg-green-500/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-green-600 sm:inline dark:bg-green-500/15 dark:text-green-400">
                                            {{ __('Aktif') }}
                                        </span>
                                    @else
                                        <span class="hidden shrink-0 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 sm:inline dark:bg-zinc-700 dark:text-zinc-400">
                                            {{ __('Nonaktif') }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ implode(' · ', $metaParts) }}
                                </p>
                            </div>
                        @if ($canUpdate)
                            </a>
                        @else
                            </div>
                        @endif

                        @if ($sheetCount > 0)
                            @canAs('manage_live_results')
                                <flux:button
                                    type="button"
                                    variant="ghost"
                                    square
                                    class="shrink-0 !text-green-600 hover:!text-green-700 dark:!text-green-400"
                                    wire:click="syncCategory({{ $category->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="syncCategory({{ $category->id }})"
                                    :aria-label="__('Sync')"
                                >
                                    <span wire:loading.remove wire:target="syncCategory({{ $category->id }})" class="inline-flex">
                                        <flux:icon :name="$justSyncedId === $category->id ? 'check' : 'arrow-path'" variant="mini" class="size-4" />
                                    </span>
                                    <span wire:loading wire:target="syncCategory({{ $category->id }})" class="inline-flex">
                                        <flux:icon name="arrow-path" variant="mini" class="size-4 animate-spin" />
                                    </span>
                                </flux:button>

                                <flux:dropdown position="bottom" align="end">
                                    <flux:button
                                        type="button"
                                        variant="ghost"
                                        icon="printer"
                                        square
                                        class="!text-orange-600 hover:!text-orange-700 dark:!text-orange-400"
                                        :aria-label="__('Print')"
                                    />
                                    <flux:menu>
                                        @foreach ($category->selected_sheets as $sheet)
                                            <flux:menu.item
                                                href="{{ route('events.live-result-categories.print', [$event, $category, 'round' => $sheet]) }}"
                                                target="_blank"
                                            >
                                                {{ $sheet }}
                                            </flux:menu.item>
                                        @endforeach
                                    </flux:menu>
                                </flux:dropdown>
                            @endcanAs
                        @endif

                        @if ($canUpdate)
                            <flux:icon
                                name="chevron-right"
                                variant="mini"
                                class="size-4 shrink-0 text-zinc-300 transition group-hover:translate-x-0.5 group-hover:text-orange-500 dark:text-zinc-600 dark:group-hover:text-orange-400"
                            />
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        @if ($this->categories->hasPages())
            <div class="mt-4 pb-2">
                {{ $this->categories->links() }}
            </div>
        @endif
    @endif
</div>
