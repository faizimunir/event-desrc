<x-layouts::app :title="__('Add Racing Committee')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Add Racing Committee')"
            :subheading="__('Racing Committees')"
            :back-href="route('racing-committees.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <livewire:racing-committees.racing-committee-form />
        </div>
    </div>
</x-layouts::app>
