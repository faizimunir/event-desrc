<x-layouts::app :title="__('Edit Rider')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:riders.rider-form :rider="$rider" />
    </div>
</x-layouts::app>
