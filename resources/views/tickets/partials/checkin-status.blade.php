@props(['registration'])

@php
    $checkin = $registration->checkin;
@endphp

@if ($checkin)
    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-800/50">
        <flux:icon name="check-circle" class="size-3.5 shrink-0 text-emerald-600 dark:text-emerald-400" />
        {{ __('Checked in') }}
    </span>
    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
        {{ $checkin->checked_in_at->translatedFormat('d M Y, H:i') }}
    </p>
@else
    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-600 ring-1 ring-inset ring-zinc-200/80 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700/80">
        <flux:icon name="clock" class="size-3.5 shrink-0 text-zinc-400 dark:text-zinc-500" />
        {{ __('Not checked in yet') }}
    </span>
@endif
