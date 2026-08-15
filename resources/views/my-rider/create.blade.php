<x-layouts::app :title="__('Add Rider')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Add Rider')"
            :subheading="__('My Rider')"
            :back-href="route('my-rider.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <livewire:riders.rider-form :for-my-rider="true" />
        </div>
    </div>
</x-layouts::app>
