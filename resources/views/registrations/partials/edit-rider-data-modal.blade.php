@props([
    'event',
    'registration',
    'modalName' => 'edit-rider-data',
    'returnTab' => null,
    'openOnLoad' => false,
])

@php
    $rider = $registration->rider;
    $canUpdate = auth()->user()->canAs('event.update');
@endphp

@if ($canUpdate)
    <flux:modal :name="$modalName" focusable class="max-w-lg" dismissible>
        <form method="post" action="{{ route('events.registrations.update-rider-data', [$event, $registration]) }}" class="max-h-[85vh] space-y-4 overflow-y-auto p-2">
            @csrf
            @if ($returnTab)
                <input type="hidden" name="return_tab" value="{{ $returnTab }}" />
            @endif
            <flux:heading size="lg">{{ __('Edit rider') }}</flux:heading>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Updates the rider profile and teams for this registration. Data must still match this registration’s bracket rules.') }}</p>

            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input name="name" type="text" :label="__('Full name')" :value="old('name', $rider->name)" required />
                <flux:input name="nickname" type="text" :label="__('Nickname')" :value="old('nickname', $rider->nickname)" />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:input name="pob" type="text" :label="__('Place of birth')" :value="old('pob', $rider->pob)" />
                <flux:input name="dob" type="date" :label="__('Date of birth')" :value="old('dob', $rider->dob?->format('Y-m-d'))" required />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <flux:select name="gender" :label="__('Gender')" required>
                    <option value="boys" @selected(old('gender', $rider->gender) === 'boys')>{{ __('Boys') }}</option>
                    <option value="girls" @selected(old('gender', $rider->gender) === 'girls')>{{ __('Girls') }}</option>
                    <option value="other" @selected(old('gender', $rider->gender) === 'other')>{{ __('Other') }}</option>
                </flux:select>
                <flux:input name="number_plate" type="text" :label="__('Number plate')" :value="old('number_plate', $rider->number_plate)" />
            </div>

            @php
                $pillboxInitialTeamIds = array_values(array_unique(array_map('intval', (array) old('team_ids', $registration->team_ids ?? []))));
            @endphp
            @livewire('team-pillbox-field', [
                'organizerId' => $event->organizer_id,
                'initialTeamIds' => $pillboxInitialTeamIds,
                'fieldLabel' => __('Teams / sponsors'),
            ], key('team-pillbox-'.$registration->id.'-'.$modalName))

            @error('team_ids')
                <p class="text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            @error('rider_data')
                <flux:callout variant="danger" class="rounded-lg text-sm">{{ $message }}</flux:callout>
            @enderror

            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button type="submit" variant="primary" size="sm">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
    @if ($openOnLoad)
        <div x-data x-init="$nextTick(() => $dispatch('modal-show', { name: @js($modalName) }))"></div>
    @endif
@endif
