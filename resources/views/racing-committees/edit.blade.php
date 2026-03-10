<x-layouts::app :title="__('Edit Racing Committee')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:racing-committees.racing-committee-form :racing-committee="$racingCommittee" />
    </div>
</x-layouts::app>
