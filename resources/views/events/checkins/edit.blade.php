<x-layouts::app :title="__('Edit check-in')">
    @php
        $rider = $checkin->registration->rider;
        $summary = $checkin->registration->checkinSummary();
    @endphp
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.show', [$event, 'tab' => 'checkin'])" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Edit check-in') }}</flux:heading>
        <flux:subheading>
            {{ $rider?->name ?? __('Rider') }}
            @if ($summary['number_plate'])
                · {{ $summary['number_plate'] }}
            @endif
            @if ($summary['bracket'])
                · {{ $summary['bracket'] }}
            @endif
        </flux:subheading>

        <form method="POST" action="{{ route('events.checkins.update', [$event, $checkin]) }}" class="max-w-lg space-y-4">
            @csrf
            @method('PUT')
            <flux:input
                name="notes"
                type="text"
                :label="__('Notes (optional)')"
                :value="old('notes', $checkin->notes)"
                :placeholder="__('e.g. Gate A')"
            />
            @error('notes')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex gap-2">
                <flux:button type="submit" variant="primary">{{ __('Update check-in') }}</flux:button>
                <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'checkin'])" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>

        @canAs('checkin.delete')
            @can('delete', $checkin)
                <form id="delete-checkin-form-{{ $checkin->id }}" method="POST" action="{{ route('events.checkins.destroy', [$event, $checkin]) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm({{ json_encode(__('Remove this check-in?')) }})) document.getElementById('delete-checkin-form-{{ $checkin->id }}').submit()"
                    >
                        {{ __('Remove') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>
</x-layouts::app>
