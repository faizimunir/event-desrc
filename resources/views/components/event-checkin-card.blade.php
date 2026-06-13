@props([
    'checkin',
    'event',
    'canEdit' => false,
])

@php
    $rider = $checkin->registration->rider;
    $canDelete = auth()->user()->canAs('checkin.delete') && auth()->user()->can('delete', $checkin);
@endphp

<div
    {{ $attributes->merge([
        'class' => 'flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800/80'
            . ($canEdit ? ' cursor-pointer transition hover:border-zinc-300 hover:bg-zinc-50 dark:hover:border-zinc-600 dark:hover:bg-zinc-800' : ''),
    ]) }}
    @if ($canEdit) wire:click="openRegistrationEdit({{ $checkin->registration_id }})" role="button" tabindex="0" @endif
>
    <div class="flex size-9 shrink-0 items-center justify-center rounded-md bg-emerald-50 px-1 font-mono text-[10px] font-semibold uppercase leading-none text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">
        <span class="truncate">{{ $checkin->registration->number_plate ?? '—' }}</span>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2">
            <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ $rider?->name ?? '—' }}
            </p>
            <flux:icon icon="check-circle" class="size-4 shrink-0 text-emerald-500 dark:text-emerald-400" aria-hidden="true" />
        </div>
        <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">
            <span>{{ $checkin->checked_in_at->format('d/m/Y H:i') }}</span>
            @if ($checkin->checkedInByUser)
                <span class="mx-1 text-zinc-300 dark:text-zinc-600">·</span>
                <span>{{ $checkin->checkedInByUser->name }}</span>
            @endif
            @if ($checkin->notes)
                <span class="mx-1 text-zinc-300 dark:text-zinc-600">·</span>
                <span>{{ $checkin->notes }}</span>
            @endif
        </p>
    </div>

    @if ($canDelete)
        <div class="flex shrink-0 items-center gap-0.5" wire:click.stop>
            <form method="POST" action="{{ route('events.checkins.destroy', [$event, $checkin]) }}" class="inline" onsubmit="return confirm('{{ __('Remove this check-in?') }}');">
                @csrf
                @method('DELETE')
                <flux:button type="submit" icon="x-mark" variant="ghost" size="sm" color="red"></flux:button>
            </form>
        </div>
    @endif
</div>
