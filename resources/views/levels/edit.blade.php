<x-layouts::app :title="__('Edit Level')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center gap-2">
            <flux:button variant="ghost" size="sm" :href="route('levels.index')" wire:navigate icon="arrow-left">
                {{ __('Back') }}
            </flux:button>
        </div>
        <flux:heading>{{ __('Edit Level') }}</flux:heading>

        <form method="post" action="{{ route('levels.update', $level) }}" class="max-w-lg space-y-6">
            @csrf
            @method('PUT')

            <flux:input name="code" type="text" :label="__('Code')" :value="old('code', $level->code)" required autofocus />
            @error('code')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="name" type="text" :label="__('Name')" :value="old('name', $level->name)" required />
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <flux:input name="order" type="number" :label="__('Order')" :value="old('order', $level->order)" min="0" required />
            @error('order')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
            @enderror

            <div class="flex flex-wrap items-center gap-2">
                <flux:button variant="primary" type="submit">{{ __('Update Level') }}</flux:button>
                <flux:button variant="ghost" :href="route('levels.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
            </div>
        </form>
        @canAs('level.delete')
            @can('delete', $level)
                <form id="delete-level-form-{{ $level->id }}" method="post" action="{{ route('levels.destroy', $level) }}" class="mt-6">
                    @csrf
                    @method('DELETE')
                    <flux:button
                        type="button"
                        variant="danger"
                        icon="trash"
                        onclick="if(confirm('{{ addslashes(__('Are you sure you want to delete this level?')) }}')) document.getElementById('delete-level-form-{{ $level->id }}').submit()"
                    >
                        {{ __('Delete Level') }}
                    </flux:button>
                </form>
            @endcan
        @endcanAs
    </div>
</x-layouts::app>
