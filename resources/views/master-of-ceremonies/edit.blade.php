<x-layouts::app :title="__('Edit Master of Ceremony')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:master-of-ceremonies.master-of-ceremony-form :master-of-ceremony="$masterOfCeremony" />
    </div>
</x-layouts::app>
