<form wire:submit="save" class="max-w-lg space-y-6">
    <flux:input wire:model="name" type="text" :label="__('Name')" placeholder="{{ __('e.g. Standard, Premium') }}" required autofocus />
    @error('name')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
    @enderror

    <flux:input wire:model="price" type="number" step="0.01" min="0" :label="__('Registration price (IDR)')" required />
    @error('price')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
    @enderror

    <div>
        <flux:label class="mb-2 block">{{ __('Race pack') }}</flux:label>
        <flux:textarea wire:model="race_pack" :placeholder="__('Describe what is included in this package (e.g. Jersey, Bib number, Goodie bag)')" rows="4" />
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Optional. Describes what the participant gets with this package.') }}</p>
        @error('race_pack')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <flux:input wire:model="sort_order" type="number" min="0" :label="__('Sort order')" />
    @error('sort_order')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
    @enderror

    <div>
        <flux:checkbox.group :label="__('Rewards')" variant="buttons">
            @foreach ($rewards as $reward)
                <flux:checkbox
                    wire:model="rewardsSelected"
                    value="{{ $reward->id }}"
                    :label="$reward->name"
                    :icon="$reward->icon ?: 'gift'"
                    class="[&:not([data-checked])]:text-zinc-400 dark:[&:not([data-checked])]:text-zinc-500"
                />
            @endforeach
        </flux:checkbox.group>
        @if ($rewards->isEmpty())
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No rewards yet. Create rewards first.') }}</p>
        @endif
        @error('rewardsSelected')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <flux:button variant="primary" type="submit">
            {{ $package ? __('Update Package') : __('Create Package') }}
        </flux:button>
        <flux:button variant="ghost" :href="route('events.packages.index', $event)" wire:navigate>{{ __('Cancel') }}</flux:button>
    </div>
</form>
