<x-layouts::app :title="__('Edit Racing Committee')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <x-admin-detail-hero
            :heading="__('Edit Racing Committee')"
            :subheading="__('Racing Committees')"
            :back-href="route('racing-committees.index')"
        />

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <livewire:racing-committees.racing-committee-form :racing-committee="$racingCommittee" />
        </div>
    </div>
</x-layouts::app>
