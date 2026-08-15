<x-layouts::app :title="__('Add Team')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Add Team')"
            :subheading="__('Teams')"
            :back-href="route('teams.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <form method="post" action="{{ route('teams.store') }}" class="max-w-lg space-y-6">
                @csrf

                <flux:input name="name" type="text" :label="__('Name')" :value="old('name')" required autofocus />
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <div>
                    <flux:label class="mb-2 block">{{ __('Organizer') }}</flux:label>
                    <flux:select name="organizer_id" :placeholder="__('Select organizer (optional)')" class="w-full">
                        <flux:select.option value="">{{ __('— None —') }}</flux:select.option>
                        @foreach ($organizers as $organizer)
                            <flux:select.option :value="$organizer->id" :selected="old('organizer_id') == $organizer->id">{{ $organizer->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @error('organizer_id')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <flux:input name="type" type="text" :label="__('Type')" :value="old('type')" />
                @error('type')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <div class="flex gap-2">
                    <flux:button variant="primary" type="submit">{{ __('Create Team') }}</flux:button>
                    <flux:button variant="ghost" :href="route('teams.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts::app>
