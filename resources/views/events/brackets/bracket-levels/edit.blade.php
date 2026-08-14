<x-layouts::app :title="__('Edit Bracket Level')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <flux:breadcrumbs class="mb-2">
            <flux:breadcrumbs.item :href="route('dashboard')">{{ __('Dashboard') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.index')" wire:navigate>{{ __('Events') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', $event)" wire:navigate>{{ $event->title }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.show', [$event, 'tab' => 'brackets'])" wire:navigate>{{ __('Brackets') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('events.brackets.bracket-levels.index', [$event, $bracket])" wire:navigate>{{ $bracket->name }} — {{ __('Levels') }}</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ __('Edit Bracket Level') }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('events.brackets.bracket-levels.index', [$event, $bracket])" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ $event->title }} — {{ $bracket->name }} — {{ __('Edit Bracket Level') }}</flux:heading>

        <form method="post" action="{{ route('events.brackets.bracket-levels.update', [$event, $bracket, $bracketLevel]) }}" class="max-w-lg space-y-6">
            @csrf
            @method('PUT')

            <div>
                <flux:label class="mb-2 block">{{ __('Level') }}</flux:label>
                <select name="event_level_id" required class="w-full rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">{{ __('— Select level —') }}</option>
                    @foreach ($levels as $level)
                        <option value="{{ $level->id }}" {{ old('event_level_id', $bracketLevel->event_level_id) == $level->id ? 'selected' : '' }}>
                            {{ $level->name }} ({{ $level->code }})
                        </option>
                    @endforeach
                </select>
                @error('event_level_id')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <flux:input name="name_original" type="text" :label="__('Name original')" :value="old('name_original', $bracketLevel->name_original)" required />
            @error('name_original')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" type="submit">{{ __('Update Bracket Level') }}</flux:button>
                <flux:button variant="ghost" :href="route('events.brackets.bracket-levels.index', [$event, $bracket])" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
        @canAs('bracket_level.delete')
            @can('delete', $bracketLevel)
                <form id="delete-bracket-level-form-{{ $bracketLevel->id }}" method="post" action="{{ route('events.brackets.bracket-levels.destroy', [$event, $bracket, $bracketLevel]) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this bracket level?')) }}')) document.getElementById('delete-bracket-level-form-{{ $bracketLevel->id }}').submit()"
                    >
                        {{ __('Delete Bracket Level') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>
</x-layouts::app>
