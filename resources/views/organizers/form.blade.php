<x-layouts::app :title="$organizer ? __('Edit Organizer') : __('Add Organizer')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:organizers.organizer-form :organizer="$organizer ?? null" />
    </div>
</x-layouts::app>
