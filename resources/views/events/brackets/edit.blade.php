<x-layouts::app :title="__('Edit Bracket')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.brackets.index', $event)" wire:navigate>{{ __('Brackets') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $bracket->name }} — {{ __('Edit') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.brackets.index', $event)" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ __('Edit Bracket') }}</flux:heading>

        <form method="post" action="{{ route('events.brackets.update', [$event, $bracket]) }}" class="max-w-lg space-y-6" x-data="{ ruleType: '{{ old('rule_type', $bracket->rule_type ?? '') }}' }">
            @csrf
            @method('PUT')

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name', $bracket->name)" placeholder="{{ $event->isCategoryUmur() ? 'e.g. U7 Boys' : 'e.g. 2018 Girls' }}" required autofocus />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div>
                <flux:label class="mb-2 block">{{ __('Gender rule') }}</flux:label>
                <flux:select name="gender_rule" :placeholder="__('No restriction')" class="w-full">
                    <flux:select.option value="" :selected="blank(old('gender_rule', $bracket->gender_rule))">{{ __('Mix') }}</flux:select.option>
                    <flux:select.option value="boys" :selected="old('gender_rule', $bracket->gender_rule) === 'boys'">{{ __('Boys') }}</flux:select.option>
                    <flux:select.option value="girls" :selected="old('gender_rule', $bracket->gender_rule) === 'girls'">{{ __('Girls') }}</flux:select.option>
                </flux:select>
                @error('gender_rule')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <flux:input name="quota" type="number" :label="__('Quota')" :value="old('quota', $bracket->quota)" min="1" placeholder="{{ __('Max riders') }}" />
            @error('quota')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror
            <flux:checkbox name="hide_quota" value="1" :checked="old('hide_quota', $bracket->hide_quota)" :label="__('Hide quota')" />

            <div x-show="ruleType === 'age'" x-cloak class="space-y-4 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Age range') }}</h3>
                <div class="grid grid-cols-2 gap-4">
                    <flux:input name="age_min" type="number" :label="__('Age min')" :value="old('age_min', $bracket->age_min)" min="0" max="120" placeholder="0" />
                    <flux:input name="age_max" type="number" :label="__('Age max')" :value="old('age_max', $bracket->age_max)" min="0" max="120" placeholder="99" />
                </div>
                <flux:input name="age_ref_date" type="date" :label="__('Reference date for age')" :value="old('age_ref_date', $bracket->age_ref_date?->format('Y-m-d'))" />
                @error('age_min')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
                @error('age_max')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
                @error('age_ref_date')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <flux:label class="mb-2 block">{{ __('Rule type (classification)') }}</flux:label>
                <flux:select name="rule_type" :placeholder="__('Rule type')" x-model="ruleType" class="w-full">
                    <flux:select.option value="age" :selected="(old('rule_type', $bracket->rule_type) ?? '') === 'age'">{{ __('Age') }}</flux:select.option>
                    <flux:select.option value="birth_year" :selected="(old('rule_type', $bracket->rule_type) ?? '') === 'birth_year'">{{ __('Birth year') }}</flux:select.option>
                </flux:select>
                @error('rule_type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div x-show="ruleType === 'birth_year'" x-cloak class="space-y-4 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4">
                <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Birth year range') }}</h3>
                <div class="grid grid-cols-2 gap-4">
                    <flux:input name="birth_year_start" type="number" :label="__('Year start')" :value="old('birth_year_start', $bracket->birth_year_start)" min="1900" max="2100" placeholder="2018" />
                    <flux:input name="birth_year_end" type="number" :label="__('Year end')" :value="old('birth_year_end', $bracket->birth_year_end)" min="1900" max="2100" placeholder="2019" />
                </div>
                @error('birth_year_start')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
                @error('birth_year_end')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" type="submit">{{ __('Update Bracket') }}</flux:button>
                <flux:button variant="ghost" :href="route('events.brackets.index', $event)" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
        @canAs('bracket.delete')
            @can('delete', $bracket)
                <form id="delete-bracket-form-{{ $bracket->id }}" method="post" action="{{ route('events.brackets.destroy', [$event, $bracket]) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this bracket?')) }}')) document.getElementById('delete-bracket-form-{{ $bracket->id }}').submit()"
                    >
                        {{ __('Delete Bracket') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>
</x-layouts::app>
