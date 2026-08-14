<x-layouts::app :title="__('Edit kategori')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'live-result'])" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Edit kategori') }}</flux:heading>
        <flux:subheading>{{ $category->title }}</flux:subheading>

        @include('admin.live-result-categories.partials.form', ['event' => $event, 'category' => $category])

        @if ($category->selected_sheets && count($category->selected_sheets) > 0)
            <div class="mt-2 flex max-w-lg flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('events.live-result-categories.sync', [$event, $category]) }}">
                    @csrf
                    <flux:button type="submit" variant="outline" icon="arrow-path">
                        {{ __('Sync') }}
                    </flux:button>
                </form>

                <select
                    id="print-round-select"
                    class="rounded-lg border border-zinc-300 bg-white px-2.5 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    data-print-url="{{ route('events.live-result-categories.print', [$event, $category]) }}"
                >
                    @foreach ($category->selected_sheets as $sheet)
                        <option value="{{ $sheet }}">{{ $sheet }}</option>
                    @endforeach
                </select>
                <flux:button type="button" variant="outline" icon="printer" id="print-preview-btn">
                    {{ __('Print') }}
                </flux:button>
            </div>
        @endif

        @canAs('event.update')
            @can('update', $event)
                <form id="delete-live-result-category-form-{{ $category->id }}" method="POST" action="{{ route('events.live-result-categories.destroy', [$event, $category]) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm({{ json_encode(__('Apakah Anda yakin ingin menghapus kategori ini?')) }})) document.getElementById('delete-live-result-category-form-{{ $category->id }}').submit()"
                    >
                        {{ __('Hapus') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('print-preview-btn');
        const select = document.getElementById('print-round-select');
        if (!btn || !select) return;

        btn.addEventListener('click', function() {
            const url = select.getAttribute('data-print-url');
            const round = select.value;
            if (url && round) {
                window.open(url + '?round=' + encodeURIComponent(round), '_blank', 'noopener');
            }
        });
    });
    </script>
</x-layouts::app>
