@php
    $embedded = (bool) ($embedded ?? false);
@endphp

<div
    data-event-checkin-scanner
    x-data="eventCheckinScanner(@js($scannerRegionId), { embedded: {{ $embedded ? 'true' : 'false' }} })"
    @class([
        'flex flex-col gap-3',
        'h-full min-h-0' => $embedded,
    ])
>
    @unless ($embedded)
        <flux:button
            type="button"
            variant="outline"
            icon="camera"
            x-on:click="openScanner()"
            x-bind:disabled="open || starting || processing"
            title="{{ __('Scan ticket QR code') }}"
        >
            <span x-show="!processing">{{ __('Scan QR') }}</span>
            <span x-show="processing" x-cloak>{{ __('Processing…') }}</span>
        </flux:button>

        <div
            x-show="error"
            x-cloak
            class="mt-3 w-full rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"
        >
            <span x-text="error"></span>
        </div>

        <template x-teleport="body">
            <div
                x-show="open"
                x-cloak
                x-transition.opacity
                class="event-checkin-scanner-overlay fixed inset-0 z-[200] flex flex-col bg-black"
                role="dialog"
                aria-modal="true"
                aria-label="{{ __('Scan ticket QR code') }}"
            >
                <div class="relative z-10 flex items-center justify-between gap-3 px-4 pb-2 pt-[max(1rem,env(safe-area-inset-top))]">
                    <p class="text-sm font-medium text-white">
                        {{ __('Point the QR code inside the box') }}
                    </p>
                    <button
                        type="button"
                        class="inline-flex size-10 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25"
                        x-on:click="closeScanner()"
                        aria-label="{{ __('Close camera') }}"
                    >
                        <flux:icon icon="x-mark" class="size-5" />
                    </button>
                </div>

                <div class="relative min-h-0 flex-1">
                    <div
                        wire:ignore
                        id="{{ $scannerRegionId }}"
                        class="event-checkin-scanner-region absolute inset-0"
                    ></div>

                    <div
                        x-show="starting"
                        x-cloak
                        class="absolute inset-0 flex items-center justify-center bg-black/70 text-sm font-medium text-white"
                    >
                        {{ __('Starting camera…') }}
                    </div>
                </div>
            </div>
        </template>
    @else
        <div class="relative z-0 h-full min-h-[280px] overflow-hidden rounded-2xl bg-black sm:min-h-[360px]">
            <div
                wire:ignore
                id="{{ $scannerRegionId }}"
                class="event-checkin-scanner-region absolute inset-0"
            ></div>

            <div class="pointer-events-none absolute inset-x-0 top-0 z-10 bg-gradient-to-b from-black/60 to-transparent px-4 pb-8 pt-4">
                <p class="text-sm font-medium text-white">
                    {{ __('Point the QR code inside the box') }}
                </p>
            </div>

            <div
                x-show="starting"
                x-cloak
                class="absolute inset-0 z-10 flex items-center justify-center bg-black/70 text-sm font-medium text-white"
            >
                {{ __('Starting camera…') }}
            </div>

            <div
                x-show="processing"
                x-cloak
                class="absolute inset-0 z-10 flex items-center justify-center bg-black/50 text-sm font-medium text-white"
            >
                {{ __('Processing…') }}
            </div>

            <div
                x-show="error"
                x-cloak
                class="absolute inset-x-3 bottom-3 z-10 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/90 dark:text-red-200"
            >
                <div class="flex items-start justify-between gap-3">
                    <span x-text="error"></span>
                    <button
                        type="button"
                        class="pointer-events-auto shrink-0 text-xs font-semibold underline"
                        x-on:click="error = null; openScanner()"
                    >
                        {{ __('Retry') }}
                    </button>
                </div>
            </div>
        </div>
    @endunless
</div>
