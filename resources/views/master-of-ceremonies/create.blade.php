<x-layouts::app :title="__('Add Master of Ceremony')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Add Master of Ceremony')"
            :subheading="__('Master of Ceremonies')"
            :back-href="route('master-of-ceremonies.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <livewire:master-of-ceremonies.master-of-ceremony-form />
        </div>
    </div>
</x-layouts::app>
