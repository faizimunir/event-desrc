<x-layouts::app :title="__('Edit Organizer')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('organizers.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Edit Organizer') }}</flux:heading>

        <form method="post" action="{{ route('organizers.update', $organizer) }}" class="max-w-lg space-y-6">
            @csrf
            @method('PUT')

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name', $organizer->name)" required autofocus />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="link" type="url" :label="__('Link')" :value="old('link', $organizer->link)" placeholder="https://..." />
            @error('link')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" type="submit">{{ __('Update Organizer') }}</flux:button>
                <flux:button variant="ghost" :href="route('organizers.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
        @canAs('organizer.delete')
            @can('delete', $organizer)
                <form id="delete-organizer-form-{{ $organizer->id }}" method="post" action="{{ route('organizers.destroy', $organizer) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this organizer?')) }}')) document.getElementById('delete-organizer-form-{{ $organizer->id }}').submit()"
                    >
                        {{ __('Delete Organizer') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>
</x-layouts::app>
