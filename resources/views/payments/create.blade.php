<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials.head', ['title' => __('Payment') . ' — ' . config('app.name')])
</head>
<body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
    @include('partials.navbar')

    <main class="mx-auto max-w-2xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-zinc-950/5 dark:bg-zinc-900 dark:ring-white/10">
            <div class="border-b border-zinc-100 px-6 py-6 dark:border-zinc-800 sm:px-8 sm:py-7">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-xs font-medium uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                            {{ __('Checkout') }}
                        </p>
                        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">
                            {{ __('Payment') }}
                        </h1>
                        <p class="mt-2 max-w-prose text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                            @if (($allowsManual ?? false) && ($allowsQris ?? false))
                                {{ __('You can pay with QRIS or manual transfer (upload proof), use whichever you prefer.') }}
                            @elseif ($allowsQris ?? false)
                                {{ __('Complete payment by scanning the QRIS code below.') }}
                            @elseif ($allowsManual ?? false)
                                {{ __('Transfer to the selected account and upload your transfer proof.') }}
                            @else
                                {{ __('Follow the instructions below to complete payment.') }}
                            @endif
                        </p>
                        @php
                            $headerPayment = $registration?->payment;
                            $showHeaderCountdown = $headerPayment
                                && $headerPayment->isPending()
                                && $headerPayment->expires_at
                                && ! $headerPayment->transfer_proof_path;
                            $headerCountdownLabel = $headerPayment && $headerPayment->method === \App\Models\Payment::METHOD_QRIS
                                ? __('Waiting for payment in')
                                : __('Upload proof within');
                        @endphp
                        @if ($showHeaderCountdown)
                            <span
                                class="mt-3 inline-flex max-w-full flex-wrap items-center gap-x-2 gap-y-0.5 rounded-full px-3 py-1.5 text-xs font-medium ring-1 ring-inset bg-amber-50 text-amber-900 ring-amber-200/80 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800/50"
                                data-expires-at="{{ $headerPayment->expires_at->format('c') }}"
                                data-time-up="{{ __('Time\'s up') }}"
                                data-upload-within="{{ $headerCountdownLabel }}"
                                x-data="paymentProofCountdown()"
                                x-init="init()">
                                <flux:icon name="clock" class="size-3.5 shrink-0 text-amber-600 dark:text-amber-400" />
                                <span class="min-w-0 leading-snug" x-text="text"></span>
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="px-6 py-6 sm:px-8 sm:py-8">
                @if (session('status'))
                    <div class="mb-6 flex gap-3 rounded-2xl border border-emerald-200/80 bg-emerald-50/90 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-100">
                        <flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <span>{{ session('status') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="mb-6 flex gap-3 rounded-2xl border border-red-200/80 bg-red-50/90 px-4 py-3 text-sm text-red-900 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-100">
                        <flux:icon name="exclamation-circle" class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if ($orderExpiredOrCancelled ?? false)
                    <div class="rounded-2xl bg-amber-50 px-5 py-5 ring-1 ring-amber-200/60 dark:bg-amber-950/30 dark:ring-amber-800/40">
                        <div class="flex gap-3">
                            <flux:icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                            <div>
                                <p class="text-sm font-medium text-amber-950 dark:text-amber-100">
                                    {{ __('This order has expired or was cancelled.') }}
                                </p>
                                <p class="mt-1 text-sm text-amber-800/90 dark:text-amber-200/80">
                                    {{ __('Please register again for the event to get a new order.') }}
                                </p>
                                <div class="mt-5 flex flex-wrap gap-2">
                                    <flux:button href="{{ route('orders.index') }}" variant="primary" size="sm">
                                        {{ __('My orders') }}
                                    </flux:button>
                                    <flux:button href="{{ route('home') }}" variant="ghost" size="sm">
                                        {{ __('Back to home') }}
                                    </flux:button>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif (!$registration)
                    <form action="{{ route('payment.verify') }}" method="post" class="space-y-6">
                        @csrf
                        <div class="space-y-2">
                            <label for="order_code" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Order code') }}
                            </label>
                            <input type="text" name="order_code" id="order_code" value="{{ old('order_code', $orderCode) }}"
                                class="block w-full rounded-xl border-0 bg-zinc-100 px-4 py-3 font-mono text-sm text-zinc-900 ring-1 ring-inset ring-zinc-200 placeholder:text-zinc-400 focus:ring-2 focus:ring-zinc-900 dark:bg-zinc-800 dark:text-zinc-100 dark:ring-zinc-700 dark:focus:ring-zinc-300"
                                placeholder="ORD-01HY1X7B1K4X8M2W6J7N9F3P5A" required>
                            @error('order_code')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-2">
                            <label for="whatsapp" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('WhatsApp number') }}
                            </label>
                            <input type="tel" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $whatsapp) }}"
                                class="block w-full rounded-xl border-0 bg-zinc-100 px-4 py-3 text-sm text-zinc-900 ring-1 ring-inset ring-zinc-200 placeholder:text-zinc-400 focus:ring-2 focus:ring-zinc-900 dark:bg-zinc-800 dark:text-zinc-100 dark:ring-zinc-700 dark:focus:ring-zinc-300"
                                placeholder="{{ __('e.g. 08123456789') }}" required>
                            @error('whatsapp')
                                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('Use the same number you used when registering.') }}
                            </p>
                        </div>
                        @if (in_array(old('payment_method', request('payment_method')), ['manual', 'qris'], true))
                            <input type="hidden" name="payment_method" value="{{ old('payment_method', request('payment_method')) }}">
                        @endif
                        @if ($errors->any() && !$errors->has('order_code') && !$errors->has('whatsapp'))
                            <div class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200/80 dark:bg-red-950/40 dark:text-red-200 dark:ring-red-900/50">
                                {{ $errors->first() }}
                            </div>
                        @endif
                        <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                            {{ __('Continue') }}
                        </flux:button>
                    </form>
                @else
                    @php
                        $payment = $registration->payment;
                        $amount = $registration->package ? $registration->package->payableAmount() : 0;
                        $proofSubmitted = $payment && $payment->isPending() && $payment->transfer_proof_path;
                        $showProofCountdown = $payment && $payment->isPending() && $payment->expires_at && !$proofSubmitted;
                        $isQris = $payment && $payment->method === \App\Models\Payment::METHOD_QRIS;
                        $staticQrisImageUrl = \App\Models\Payment::getStaticQrisImageUrl();
                        $allowsManual = $allowsManual ?? false;
                        $allowsQris = $allowsQris ?? false;
                        $manualAccounts = $manualAccounts ?? collect();
                        $manualMisconfigured = $allowsManual && $manualAccounts->isEmpty();
                        $qrisPendingBlocksManual = $payment && $payment->method === \App\Models\Payment::METHOD_QRIS && $payment->isPending();
                        $showQrisFirst = ($preferredPaymentMethod ?? null) !== 'manual';
                        $lockToSelectedMethod = $payment && $payment->isPending() && is_string($payment->method) && $payment->method !== '';
                        $hideManualForQrisChoice = ($preferredPaymentMethod ?? null) === 'qris' && $allowsQris;
                        $orderForChange = $registration?->order;
                        $canChangeMethod = $orderForChange && ($registration?->payment?->isPending() ?? false) && ($orderForChange?->isPendingUnpaid() ?? false);
                    @endphp

                    <div class="space-y-8">
                        @if ($allowsManual && ! $allowsQris && $manualMisconfigured)
                            <div class="flex gap-3 rounded-2xl bg-amber-50 px-4 py-4 text-sm text-amber-950 ring-1 ring-amber-200/70 dark:bg-amber-950/30 dark:text-amber-100 dark:ring-amber-800/50">
                                <flux:icon name="exclamation-triangle" class="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                                <span>{{ __('Manual payment is enabled for this event, but no bank account has been assigned. Please contact the organizer.') }}</span>
                            </div>
                        @endif
                        @if ($proofSubmitted)
                            <div class="flex gap-3 rounded-2xl bg-sky-50 px-4 py-4 text-sm text-sky-950 ring-1 ring-sky-200/70 dark:bg-sky-950/35 dark:text-sky-100 dark:ring-sky-800/50">
                                <flux:icon name="document-text" class="mt-0.5 size-5 shrink-0 text-sky-600 dark:text-sky-400" />
                                <div>
                                    <p class="font-medium">{{ __('Status') }}: {{ __('Submitted') }}</p>
                                    <p class="mt-1 text-sky-900/85 dark:text-sky-200/80">
                                        Pembayaran sedang di review oleh admin. Tunggu 1 x 24 jam.
                                    </p>
                                </div>
                            </div>
                        @endif

                        @if (!$proofSubmitted && !($payment && $payment->isSuccess()))
                            <div class="rounded-2xl bg-zinc-50 p-5 ring-1 ring-zinc-200/80 dark:bg-zinc-800/40 dark:ring-zinc-700/80">
                                <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500">
                                    {{ __('Registration') }}
                                </p>
                                <p class="mt-1 text-base font-semibold text-zinc-900 dark:text-zinc-50">
                                    {{ $registration->event->title }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ $registration->rider->name }} · {{ $registration->bracket->name }}
                                    @if ($registration->package)
                                        · {{ $registration->package->name }}
                                    @endif
                                </p>
                            </div>

                            <div class="flex flex-col gap-8">
                                @if ($allowsQris && (! $lockToSelectedMethod || ($payment && $payment->method === \App\Models\Payment::METHOD_QRIS)))
                                    <section class="rounded-2xl p-6 ring-1 ring-zinc-200/90 dark:ring-zinc-700/90 {{ $showQrisFirst ? 'order-1' : 'order-2' }}">
                                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="size-2 shrink-0 rounded-full bg-violet-500"></span>
                                                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                                        {{ __('Pay via QRIS') }}
                                                    </h2>
                                                </div>
                                                <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                                    @if ($isQris && $staticQrisImageUrl)
                                                        {{ __('Scan the QRIS code and pay exact amount below. Confirmation is automatic when amount is matched.') }}
                                                    @else
                                                        {{ __('Pay with QRIS and use the exact amount below. Confirmation is automatic when amount is matched.') }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        @if ($isQris && $payment->amount)
                                            @php
                                                $qrisUniqueCode = null;
                                                $qrisBase = (int) round((float) ($amount ?? 0));
                                                $qrisTotal = (int) round((float) $payment->amount);
                                                $qrisDelta = $qrisTotal - $qrisBase;
                                                if ($qrisDelta >= \App\Models\Payment::MANUAL_UNIQUE_SUFFIX_MIN && $qrisDelta <= \App\Models\Payment::MANUAL_UNIQUE_SUFFIX_MAX) {
                                                    $qrisUniqueCode = str_pad((string) $qrisDelta, 2, '0', STR_PAD_LEFT);
                                                }
                                            @endphp
                                            @if ($staticQrisImageUrl)
                                                <div class="mt-6 rounded-2xl bg-zinc-50 dark:bg-zinc-800/50">
                                                        <img
                                                            src="{{ $staticQrisImageUrl }}"
                                                            alt="{{ __('QRIS payment code') }}"
                                                            class="max-h-72 w-full max-w-full rounded-2xl bg-white object-contain p-3 shadow-sm ring-1 ring-zinc-200/80 dark:ring-zinc-600/80"
                                                            loading="lazy"
                                                        >
                                                </div>
                                            @endif
                                            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                                                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/50 sm:col-span-2">
                                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Exact amount (IDR)') }}
                                                    </p>
                                                    <p class="mt-2 font-mono text-2xl font-bold tabular-nums text-violet-700 dark:text-violet-300">
                                                        {{ 'Rp ' . number_format((float) $payment->amount, 0, ',', '.') }}
                                                    </p>
                                                    @if ($qrisUniqueCode)
                                                        <p class="mt-2 text-xs font-medium text-violet-700 dark:text-violet-300">
                                                            {{ __('Unique code') }}: {{ $qrisUniqueCode }}
                                                        </p>
                                                    @endif
                                                    <p class="mt-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Pay this exact amount so we can match your payment. Do not round.') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                    </section>
                                @endif

                                @if (! $hideManualForQrisChoice && $allowsManual && (! $lockToSelectedMethod || ($payment && $payment->method === 'manual')) && ! $qrisPendingBlocksManual && ! $manualMisconfigured)
                                    <section class="rounded-2xl p-6 ring-1 ring-zinc-200/90 dark:ring-zinc-700/90 {{ $showQrisFirst ? 'order-2' : 'order-1' }}">
                                        <div class="flex items-center gap-2">
                                            <span class="size-2 shrink-0 rounded-full bg-amber-500"></span>
                                            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                                {{ __('Manual transfer') }}
                                            </h2>
                                        </div>
                                        <p class="mt-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">
                                            @if ($manualAccounts->count() > 1)
                                                {{ __('Choose a bank account below when uploading your proof (if more than one is offered).') }}
                                            @else
                                                {{ __('Transfer to the account below, then upload your proof.') }}
                                            @endif
                                        </p>
                                        @if ($manualAccounts->count() === 1)
                                            @php $acc = $manualAccounts->first(); @endphp
                                            <div class="mt-4 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $acc->acc_bank }}</p>
                                                <p class="mt-1 font-mono text-lg font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">{{ $acc->acc_number }}</p>
                                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $acc->acc_name }}</p>
                                            </div>
                                        @elseif ($manualAccounts->count() > 1)
                                            <ul class="mt-4 space-y-3">
                                                @foreach ($manualAccounts as $acc)
                                                    <li class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                                                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $acc->acc_bank }}</p>
                                                        <p class="mt-1 font-mono text-sm font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">{{ $acc->acc_number }}</p>
                                                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $acc->acc_name }}</p>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <div class="mt-4 rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $bank['bank_name'] }}</p>
                                                <p class="mt-1 font-mono text-lg font-semibold tabular-nums text-zinc-800 dark:text-zinc-200">{{ $bank['account_number'] }}</p>
                                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $bank['account_holder'] }}</p>
                                            </div>
                                        @endif

                                        @if ($payment && $payment->method === 'manual' && $payment->manual_transfer_amount && ! $proofSubmitted)
                                            <div class="mt-4 grid gap-4">
                                                @if ($payment->manualUniqueSuffixFormatted())
                                                    <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                                                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                            {{ __('Unique payment code') }}
                                                        </p>
                                                        <p class="mt-2 font-mono text-2xl font-bold tracking-widest text-amber-700 dark:text-amber-300">
                                                            {{ $payment->manualUniqueSuffixFormatted() }}
                                                        </p>
                                                    </div>
                                                @endif
                                                <div class="rounded-2xl bg-zinc-50 p-4 dark:bg-zinc-800/50 sm:col-span-1 {{ ! $payment->manualUniqueSuffixFormatted() ? 'sm:col-span-2' : '' }}">
                                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Exact amount to transfer (IDR)') }}
                                                    </p>
                                                    <p class="mt-2 font-mono text-2xl font-bold tabular-nums text-amber-800 dark:text-amber-200">
                                                        {{ $payment->formatted_manual_transfer_amount }}
                                                    </p>
                                                    <p class="mt-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                                                        {{ __('Transfer this exact total so we can identify your payment. Do not round.') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif
                                    </section>
                                @endif
                            </div>
                        @endif

                        @if ($canChangeMethod)
                                <p class="text-sm">
                                    Need a different payment method ?
                                    <a href="{{ route('orders.show', $orderForChange) . '?change_payment_method=1' }}" class="font-medium text-zinc-900 underline decoration-zinc-300 underline-offset-2 transition hover:text-zinc-700 dark:text-zinc-200 dark:decoration-zinc-600 dark:hover:text-white">
                                        {{ __('Change payment method') }}
                                    </a>
                                </p>
                        @endif

                        @if ($payment && $payment->isSuccess())
                            <div class="flex gap-3 rounded-2xl bg-emerald-50 px-4 py-4 text-sm text-emerald-950 ring-1 ring-emerald-200/70 dark:bg-emerald-950/35 dark:text-emerald-100 dark:ring-emerald-800/50">
                                <flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                <span>{{ __('Your payment has been verified.') }}</span>
                            </div>
                        @elseif ($payment && $payment->isFailed())
                            <div class="flex gap-3 rounded-2xl bg-red-50 px-4 py-4 text-sm text-red-950 ring-1 ring-red-200/70 dark:bg-red-950/35 dark:text-red-100 dark:ring-red-900/50">
                                <flux:icon name="x-circle" class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-400" />
                                <div>
                                    <p>{{ __('This payment was rejected.') }}</p>
                                    @if ($payment->admin_notes)
                                        <p class="mt-2 text-red-900/90 dark:text-red-200/90">{{ $payment->admin_notes }}</p>
                                    @endif
                                </div>
                            </div>
                        @elseif (! $hideManualForQrisChoice && $allowsManual && (! $lockToSelectedMethod || ($payment && $payment->method === 'manual')) && ! $manualMisconfigured && ! $proofSubmitted && ! $qrisPendingBlocksManual)
                            <form action="{{ route('payment.store') }}" method="post" enctype="multipart/form-data" class="space-y-6">
                                @csrf
                                <input type="hidden" name="order_code" value="{{ $orderCode }}">
                                <input type="hidden" name="whatsapp" value="{{ $whatsapp }}">
                                @if ($manualAccounts->count() === 1)
                                    <input type="hidden" name="manual_account_id" value="{{ $manualAccounts->first()->id }}">
                                @elseif ($manualAccounts->count() > 1)
                                    <fieldset class="space-y-3">
                                        <legend class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Transfer to which account?') }}</legend>
                                        @foreach ($manualAccounts as $acc)
                                            <label class="flex cursor-pointer gap-3 rounded-2xl bg-zinc-50 p-4 ring-1 ring-zinc-200/80 transition hover:bg-zinc-100 has-[:checked]:ring-2 has-[:checked]:ring-zinc-900 dark:bg-zinc-800/50 dark:ring-zinc-700/80 dark:hover:bg-zinc-800 dark:has-[:checked]:ring-zinc-300">
                                                <input type="radio" name="manual_account_id" value="{{ $acc->id }}" class="mt-1 border-zinc-300 text-zinc-900 focus:ring-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:focus:ring-zinc-400" required
                                                    @checked((string) old('manual_account_id') === (string) $acc->id)>
                                                <span class="min-w-0">
                                                    <span class="block font-medium text-zinc-900 dark:text-zinc-100">{{ $acc->acc_bank }}</span>
                                                    <span class="block font-mono text-sm text-zinc-700 dark:text-zinc-300">{{ $acc->acc_number }}</span>
                                                    <span class="block text-sm text-zinc-500 dark:text-zinc-400">{{ $acc->acc_name }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </fieldset>
                                    @error('manual_account_id')
                                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                @endif

                                <div class="space-y-2">
                                    <label for="transfer_proof" class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                        {{ __('Transfer proof') }} <span class="font-normal text-zinc-500">({{ __('photo or screenshot') }})</span>
                                    </label>
                                    <input type="file" name="transfer_proof" id="transfer_proof" accept="image/*"
                                        class="block w-full text-sm text-zinc-600 file:mr-4 file:rounded-xl file:border-0 file:bg-zinc-900 file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-white hover:file:bg-zinc-800 dark:text-zinc-400 dark:file:bg-zinc-100 dark:file:text-zinc-900 dark:hover:file:bg-zinc-200"
                                        required>
                                    @error('transfer_proof')
                                        <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Max 5 MB. JPG, PNG.') }}</p>
                                </div>

                                <flux:button type="submit" variant="primary" class="w-full sm:w-auto">
                                    {{ __('Upload proof') }}
                                </flux:button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <p class="mt-8 text-center">
            <a href="{{ route('home') }}" class="text-sm text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200">
                {{ __('Back to home') }}
            </a>
        </p>
    </main>

    @include('partials.footer')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('paymentProofCountdown', () => ({
                expiresAt: null,
                text: '—',
                init() {
                    const el = this.$el;
                    this.expiresAt = new Date(el.dataset.expiresAt);
                    const timeUpLabel = el.dataset.timeUp || "Time's up";
                    const uploadWithinLabel = el.dataset.uploadWithin || "Upload proof within";
                    const update = () => {
                        const sec = Math.max(0, Math.floor((this.expiresAt - new Date()) / 1000));
                        if (sec <= 0) {
                            this.text = timeUpLabel;
                            return;
                        }
                        const m = Math.floor(sec / 60);
                        const s = sec % 60;
                        this.text = uploadWithinLabel + ' ' + String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                    };
                    update();
                    setInterval(update, 1000);
                }
            }));
        });
    </script>
    @fluxScripts
</body>
</html>
