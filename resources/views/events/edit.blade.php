<x-layouts::app :title="__('Edit Event')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Edit Event')"
            :subheading="__('Events')"
            :back-href="route('events.show', $event)"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <livewire:events.event-form :event="$event" />
        </div>
    </div>
</x-layouts::app>
