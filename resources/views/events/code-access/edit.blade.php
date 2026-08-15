<x-layouts::app :title="__('Edit code')" :unified-header="true">
    <div class="flex h-full w-full flex-1 flex-col">
        <div class="users-hero-shell sticky top-0 z-10 bg-gradient-to-br from-orange-500 via-orange-500 to-amber-500 shadow-[0_12px_32px_-14px_rgba(249,115,22,0.55)] dark:from-orange-600 dark:via-orange-600 dark:to-amber-600 lg:-mx-4">
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute -right-8 -top-8 size-32 rounded-full bg-white/10 blur-2xl"></div>
            </div>

            <div class="relative space-y-3 px-4 pb-3 pt-[max(0.5rem,env(safe-area-inset-top))] sm:px-5 sm:pb-4 lg:space-y-3.5 lg:pt-4">
                <div class="flex items-center gap-2.5 lg:hidden">
                    <flux:sidebar.toggle
                        icon="bars-2"
                        inset="left"
                        class="!size-9 !rounded-xl !border !border-white/25 !bg-white/15 !text-white hover:!bg-white/25"
                    />

                    <div class="flex min-w-0 flex-1 items-center gap-2.5">
                        <img
                            src="{{ asset('logo-mini-dark.webp') }}"
                            alt="{{ config('app.name') }}"
                            class="h-9 w-auto shrink-0 object-contain"
                        >
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs text-orange-100/80">
                                {{ $event->title }}
                            </p>
                            <h1 class="truncate text-sm font-semibold text-white">
                                {{ __('Edit code') }}
                            </h1>
                        </div>
                    </div>

                    <flux:dropdown position="bottom" align="end">
                        <button
                            type="button"
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-white/25 bg-white/15 text-xs font-semibold text-white transition hover:bg-white/25"
                            aria-label="{{ __('Account menu') }}"
                        >
                            {{ auth()->user()->initials() }}
                        </button>

                        @include('partials.mobile-user-menu')
                    </flux:dropdown>
                </div>

                <div class="hidden items-center justify-between gap-3 lg:flex">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-orange-100/90">
                            {{ $event->title }}
                        </p>
                        <h1 class="truncate text-xl font-semibold tracking-tight text-white">
                            {{ __('Edit code') }}
                        </h1>
                    </div>

                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('events.show', [$event, 'tab' => 'code-access'])"
                        wire:navigate
                        icon="arrow-left"
                        class="shrink-0 !border !border-white/25 !bg-white/15 !text-white hover:!bg-white/25"
                    >
                        {{ __('Back') }}
                    </flux:button>
                </div>

                <div class="flex items-center gap-2 lg:hidden">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        :href="route('events.show', [$event, 'tab' => 'code-access'])"
                        wire:navigate
                        icon="arrow-left"
                        class="users-hero-action shrink-0"
                        :aria-label="__('Back')"
                    />
                </div>
            </div>
        </div>

        <div class="users-hero-content flex flex-1 flex-col gap-4 pt-4 pb-6">
            <form method="POST" action="{{ route('events.code-access.update', [$event, $codeAccess]) }}" class="max-w-lg space-y-4">
                @csrf
                @method('PUT')
                <flux:input name="code" type="text" :label="__('Code')" :value="old('code', $codeAccess->code)" :placeholder="__('e.g. EARLY2025')" required autofocus />
                @error('code')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <flux:input name="name" type="text" :label="__('Name (optional)')" :value="old('name', $codeAccess->name)" :placeholder="__('e.g. Early Bird')" />
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input name="valid_from" type="datetime-local" :label="__('Valid from (optional)')" :value="old('valid_from', $codeAccess->valid_from?->format('Y-m-d\\TH:i'))" />
                    <flux:input name="valid_until" type="datetime-local" :label="__('Valid until (optional)')" :value="old('valid_until', $codeAccess->valid_until?->format('Y-m-d\\TH:i'))" />
                </div>
                @error('valid_from')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror
                @error('valid_until')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <flux:input name="usage_limit" type="number" min="1" :label="__('Usage limit (optional)')" :value="old('usage_limit', $codeAccess->usage_limit)" :placeholder="__('Max uses, leave empty for unlimited')" />
                @error('usage_limit')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                @enderror

                <div class="flex gap-2">
                    <flux:button type="submit" variant="primary">{{ __('Update code') }}</flux:button>
                    <flux:button variant="ghost" :href="route('events.show', [$event, 'tab' => 'code-access'])" wire:navigate>{{ __('Cancel') }}</flux:button>
                </div>
            </form>

            <form id="delete-code-access-form-{{ $codeAccess->id }}" method="POST" action="{{ route('events.code-access.destroy', [$event, $codeAccess]) }}" class="mt-2">
                @csrf
                @method('DELETE')
                <flux:button
                    type="button"
                    variant="danger"
                    icon="trash"
                    onclick="if(confirm({{ json_encode(__('Remove this code?')) }})) document.getElementById('delete-code-access-form-{{ $codeAccess->id }}').submit()"
                >
                    {{ __('Remove') }}
                </flux:button>
            </form>
        </div>
    </div>
</x-layouts::app>
