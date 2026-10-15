{{--
    Zona hapus untuk bracket/package.

    @param string $kind          'bracket' | 'package'
    @param \App\Models\Event $event
    @param \App\Models\Bracket|\App\Models\Package $item
    @param string $deleteRoute   URL DELETE
    @param string $transferRoute URL POST pengalihan
    @param string $targetField   nama field target (target_bracket_id / target_package_id)
    @param array<int, array{id:int,label:string,payable:?float}> $targets
    @param array<int, string> $cascadeNotes  data lain yang ikut terhapus (untuk modal konfirmasi)
    @param float|null $currentPayable total bayar item saat ini (khusus package, untuk peringatan harga)
--}}
@php
    $user = auth()->user();
    $label = $kind === 'bracket' ? __('Bracket') : __('Package');
    $labelLower = $kind === 'bracket' ? __('bracket') : __('package');
    $modalKey = $kind.'-'.$item->id;
    $registrationsCount = $item->registrationsCount();
    $isSuperAdmin = $user->hasRole('super_admin');
    $canDelete = $user->canAs($kind.'.delete') && $user->can('delete', $item);
    $canTransfer = $user->canAs($kind.'.update') && $user->canAs('event.update');
    $failures = session('transfer_failures', []);
    $currentPayable = $currentPayable ?? null;
    $targetPrices = collect($targets)->mapWithKeys(fn ($t) => [(string) $t['id'] => $t['payable'] ?? null])->all();
@endphp

@if (! empty($failures))
    <div class="mt-4 max-w-lg rounded-lg border border-red-200 bg-red-50 p-4 text-sm dark:border-red-900/50 dark:bg-red-950/30" role="alert">
        <p class="font-medium text-red-800 dark:text-red-300">
            {{ __(':count registrants could not be moved and are still in this :kind:', ['count' => count($failures), 'kind' => $labelLower]) }}
        </p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-red-700 dark:text-red-300/90">
            @foreach ($failures as $failure)
                <li>
                    <a href="{{ route('events.registrations.show', [$event, $failure['registration_id']]) }}" class="font-medium underline" wire:navigate>{{ $failure['rider'] }}</a>
                    — {{ $failure['reason'] }}
                </li>
            @endforeach
        </ul>
        <p class="mt-2 text-xs text-red-700/80 dark:text-red-300/70">
            {{ __('Fix the cause (or move them one by one from the registration page), then try again.') }}
        </p>
    </div>
@endif

@if ($canDelete || $canTransfer)
    <div class="mt-4 max-w-lg space-y-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        @if ($registrationsCount === 0)
            @if ($canDelete)
                <flux:modal.trigger :name="'delete-'.$modalKey">
                    <flux:button type="button" variant="danger" icon="trash">{{ __('Delete :item', ['item' => $label]) }}</flux:button>
                </flux:modal.trigger>

                <flux:modal :name="'delete-'.$modalKey" class="max-w-md" dismissible>
                    <form method="post" action="{{ $deleteRoute }}" class="space-y-4 p-2">
                        @csrf
                        @method('DELETE')
                        <flux:heading size="lg">{{ __('Delete :item?', ['item' => $labelLower]) }}</flux:heading>
                        <p class="text-sm text-zinc-600 dark:text-zinc-300">
                            {{ __('No registrants are using ":name", so it is safe to delete.', ['name' => $item->name]) }}
                        </p>
                        @if (! empty($cascadeNotes))
                            <div class="rounded-lg bg-zinc-50 p-3 text-sm text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                <p class="font-medium">{{ __('This will also remove:') }}</p>
                                <ul class="mt-1 list-disc pl-5">
                                    @foreach ($cascadeNotes as $note)
                                        <li>{{ $note }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="flex justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            <flux:modal.close>
                                <flux:button type="button" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="danger" size="sm" icon="trash">{{ __('Yes, delete') }}</flux:button>
                        </div>
                    </form>
                </flux:modal>
            @endif
        @else
            <div class="flex items-start gap-3 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-700/60 dark:bg-amber-950/30 dark:text-amber-200">
                <flux:icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0" />
                <div>
                    <p class="font-semibold">
                        {{ __('This :kind cannot be deleted yet.', ['kind' => $labelLower]) }}
                    </p>
                    <p class="mt-1">
                        {{ trans_choice(':count registrant is still registered here.|:count registrants are still registered here.', $registrationsCount, ['count' => $registrationsCount]) }}
                        {{ __('Deleting it would also erase their registrations, orders, payments, tickets and check-ins.') }}
                        {{ $kind === 'bracket'
                            ? __('Move them to another bracket first.')
                            : __('Move them to another active package first.') }}
                    </p>
                </div>
            </div>

            @if ($canTransfer)
                @if (count($targets) > 0)
                    <flux:modal.trigger :name="'transfer-'.$modalKey">
                        <flux:button type="button" variant="primary" icon="arrow-path">{{ __('Transfer registrants') }}</flux:button>
                    </flux:modal.trigger>

                    <flux:modal :name="'transfer-'.$modalKey" class="max-w-md" focusable dismissible>
                        <form
                            method="post"
                            action="{{ $transferRoute }}"
                            class="space-y-4 p-2"
                            x-data="{ target: '', prices: @js($targetPrices), current: @js($currentPayable) }"
                        >
                            @csrf
                            <flux:heading size="lg">{{ __('Transfer registrants') }}</flux:heading>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                @if ($kind === 'bracket')
                                    {{ __('Move all :count registrants of ":name" to another bracket. Each rider is checked separately (age or birth year, gender, quota, duplicates). Riders who do not qualify stay here and are listed afterwards.', ['count' => $registrationsCount, 'name' => $item->name]) }}
                                @else
                                    {{ __('Move all :count registrants of ":name" to another active package. Each registrant is checked separately (quota, jersey size). Those who do not qualify stay here and are listed afterwards.', ['count' => $registrationsCount, 'name' => $item->name]) }}
                                @endif
                            </p>

                            <flux:select name="{{ $targetField }}" x-model="target" :label="$kind === 'bracket' ? __('Target bracket') : __('Target package')" required>
                                <option value="">{{ __('Select…') }}</option>
                                @foreach ($targets as $target)
                                    <option value="{{ $target['id'] }}">{{ $target['label'] }}</option>
                                @endforeach
                            </flux:select>
                            @error($targetField)
                                <p class="text-sm text-red-600 dark:text-red-400" role="alert">{{ $message }}</p>
                            @enderror

                            @if ($kind === 'package')
                                <p
                                    x-show="target !== '' && current !== null && prices[target] !== null && Number(prices[target]) !== Number(current)"
                                    x-cloak
                                    class="rounded-lg bg-amber-50 p-3 text-sm text-amber-800 dark:bg-amber-950/30 dark:text-amber-200"
                                >
                                    {{ __('The target package has a different price. Existing payment records will NOT be changed; only the package on each registration is updated.') }}
                                </p>
                            @endif

                            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="primary" size="sm" icon="arrow-path" x-bind:disabled="target === ''">
                                    {{ __('Transfer') }}
                                </flux:button>
                            </div>
                        </form>
                    </flux:modal>
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $kind === 'bracket'
                            ? __('There is no other bracket in this event. Create one first, then transfer.')
                            : __('There is no other active package in this event. Create or activate one first, then transfer.') }}
                    </p>
                @endif
            @endif

            @if ($canDelete && $isSuperAdmin)
                <div class="border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <flux:modal.trigger :name="'force-delete-'.$modalKey">
                        <flux:button type="button" variant="ghost" size="sm" icon="trash" class="!text-red-600 dark:!text-red-400">
                            {{ __('Force delete (super admin)') }}
                        </flux:button>
                    </flux:modal.trigger>

                    <flux:modal :name="'force-delete-'.$modalKey" class="max-w-md" dismissible>
                        <form method="post" action="{{ $deleteRoute }}" class="space-y-4 p-2">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="force_delete" value="1">
                            <flux:heading size="lg">{{ __('Force delete :item', ['item' => $labelLower]) }}</flux:heading>
                            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                                <p class="font-semibold">
                                    {{ trans_choice('This permanently deletes :count registrant and everything attached to it.|This permanently deletes :count registrants and everything attached to them.', $registrationsCount, ['count' => $registrationsCount]) }}
                                </p>
                                <p class="mt-1">{{ __('Registrations, orders, payments, tickets, check-ins and WhatsApp logs are removed. This cannot be undone.') }}</p>
                            </div>
                            <flux:input
                                name="confirm_name"
                                type="text"
                                :label="__('Type ":name" to confirm', ['name' => $item->name])"
                                autocomplete="off"
                                required
                            />
                            <div class="flex justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                <flux:modal.close>
                                    <flux:button type="button" variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                                </flux:modal.close>
                                <flux:button type="submit" variant="danger" size="sm" icon="trash">{{ __('Delete permanently') }}</flux:button>
                            </div>
                        </form>
                    </flux:modal>
                </div>
            @endif
        @endif
    </div>
@endif
