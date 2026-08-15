<x-layouts::app :title="$organizer ? __('Edit Organizer') : __('Add Organizer')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="$organizer ? __('Edit Organizer') : __('Add Organizer')"
            :subheading="__('Organizers')"
            :back-href="route('organizers.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <livewire:organizers.organizer-form :organizer="$organizer ?? null" />
        </div>
    </div>
</x-layouts::app>
