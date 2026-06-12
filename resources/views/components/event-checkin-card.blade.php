@props([
    'checkin',
    'event',
])

@php
    $rider = $checkin->registration->rider;
    $canUpdate = auth()->user()->canAs('checkin.update') && auth()->user()->can('update', $checkin);
    $canDelete = auth()->user()->canAs('checkin.delete') && auth()->user()->can('delete', $checkin);
    $hasActions = $canUpdate || $canDelete;
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-2.5 dark:border-zinc-700 dark:bg-zinc-800/80']) }}>
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

    @if ($hasActions)
        <div class="flex shrink-0 items-center gap-0.5">
            @if ($canUpdate)
                <flux:button size="sm" variant="ghost" x-data x-on:click="$dispatch('open-modal', 'edit-checkin-{{ $checkin->id }}')">{{ __('Edit') }}</flux:button>
                <flux:modal :name="'edit-checkin-'.$checkin->id" focusable class="max-w-md" dismissible>
                    <form method="POST" action="{{ route('events.checkins.update', [$event, $checkin]) }}">
                        @csrf
                        @method('PUT')
                        <div class="space-y-3 p-2">
                            <flux:heading size="lg">{{ __('Edit check-in') }}</flux:heading>
                            <flux:input name="notes" type="text" :label="__('Notes')" :value="$checkin->notes" />
                            <div class="flex justify-end gap-2">
                                <flux:modal.close>
                                    <flux:button variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
                            </div>
                        </div>
                    </form>
                </flux:modal>
            @endif
            @if ($canDelete)
                <form method="POST" action="{{ route('events.checkins.destroy', [$event, $checkin]) }}" class="inline" onsubmit="return confirm('{{ __('Remove this check-in?') }}');">
                    @csrf
                    @method('DELETE')
                    <flux:button type="submit" variant="ghost" size="sm" color="red">{{ __('Remove') }}</flux:button>
                </form>
            @endif
        </div>
    @endif
</div>
