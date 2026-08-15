@props([
    'rundowns',
    'emptyMessage' => null,
])

@php
    $emptyMessage ??= __('No rundown for this event.');
@endphp

@if ($rundowns->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700']) }}>
        <table class="w-full border-collapse text-left text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                <tr class="border-b border-zinc-200 dark:border-zinc-700">
                    <th class="w-[38%] px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Time') }}
                    </th>
                    <th class="px-4 py-2.5 text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Activity / Brackets') }}
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rundowns as $rundown)
                    <tr class="border-b border-zinc-200 last:border-b-0 dark:border-zinc-700">
                        <td class="whitespace-nowrap px-4 py-3 font-semibold tabular-nums text-zinc-900 dark:text-zinc-100">
                            {{ $rundown->formattedTimeRange() }}
                        </td>
                        <td class="px-4 py-3 font-medium uppercase tracking-wide text-zinc-900 dark:text-zinc-100">
                            {{ $rundown->displayLabel() }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $emptyMessage }}</p>
@endif
