<x-layouts::app :title="__('Add Rider')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <livewire:riders.rider-form :for-my-rider="true" />
    </div>
</x-layouts::app>
