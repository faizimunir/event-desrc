@extends('layouts.drag-race-timer')

@section('title', __('Drag Race Timer'))

@section('content')
    <div
        id="drag-race-root"
        class="relative mx-auto flex min-h-screen max-w-5xl flex-col gap-6 px-4 py-6 sm:px-6"
        data-state-url="{{ route('drag-race-timer.state') }}"
        data-start-url="{{ route('drag-race-timer.start') }}"
        data-stop-a-url="{{ route('drag-race-timer.stop-a') }}"
        data-stop-b-url="{{ route('drag-race-timer.stop-b') }}"
        data-reset-url="{{ route('drag-race-timer.reset') }}"
        data-clear-history-url="{{ route('drag-race-timer.clear-history') }}"
        data-time-url="{{ url('/api/time') }}"
        data-broadcast-driver="{{ config('broadcasting.default') }}"
        data-pusher-key="{{ $pusherKey }}"
        data-pusher-cluster="{{ $pusherCluster }}"
        data-pusher-host="{{ $pusherHost }}"
        data-pusher-port="{{ $pusherPort }}"
        data-pusher-scheme="{{ $pusherScheme }}"
        data-initial-state="{{ json_encode($initialState, JSON_THROW_ON_ERROR) }}"
        data-initial-history="{{ json_encode($initialHistory, JSON_THROW_ON_ERROR) }}"
    >
        <header class="flex flex-col gap-2 border-b border-zinc-800 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-white">{{ __('Drag Race Timer') }}</h1>
                <p class="mt-1 text-sm text-zinc-400">{{ __('BMX drag — Lane A & B · synced start via WebSocket') }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('dashboard') }}" class="rounded-lg border border-zinc-700 px-3 py-1.5 text-sm text-zinc-300 hover:bg-zinc-800">
                    {{ __('Dashboard') }}
                </a>
                <span id="connection-pill" class="rounded-full border border-zinc-700 px-2.5 py-0.5 text-xs font-medium text-zinc-400">{{ __('Connecting…') }}</span>
            </div>
        </header>

        <div id="go-overlay" class="pointer-events-none fixed inset-0 z-40 hidden flex items-center justify-center">
            <span id="go-text" class="text-8xl font-black tracking-tighter text-emerald-400 opacity-0 transition-opacity duration-200 sm:text-9xl"></span>
        </div>
        <div id="countdown-overlay" class="pointer-events-none fixed inset-0 z-30 hidden flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <span id="countdown-number" class="select-none text-[10rem] font-black tabular-nums leading-none text-white drop-shadow-lg sm:text-[14rem]"></span>
        </div>

        <section class="rounded-2xl border border-zinc-800 bg-zinc-900/60 p-6 shadow-xl backdrop-blur">
            <div class="font-mono text-6xl font-semibold tabular-nums tracking-tight text-white sm:text-7xl md:text-8xl" id="main-timer">00:00.000</div>
            <p class="mt-2 text-xs uppercase tracking-widest text-zinc-500">{{ __('Race clock') }}</p>
        </section>

        <div class="grid gap-4 sm:grid-cols-2">
            <div id="lane-a-card" class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-5 transition-colors duration-300">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-zinc-400">{{ __('Lane A') }}</span>
                    <span id="lane-a-badge" class="hidden rounded px-1.5 py-px text-xs font-semibold uppercase"></span>
                </div>
                <div class="mt-3 font-mono text-4xl font-semibold tabular-nums text-zinc-100 sm:text-5xl" id="lane-a-time">—.———</div>
            </div>
            <div id="lane-b-card" class="rounded-2xl border border-zinc-800 bg-zinc-900/40 p-5 transition-colors duration-300">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-zinc-400">{{ __('Lane B') }}</span>
                    <span id="lane-b-badge" class="hidden rounded px-1.5 py-px text-xs font-semibold uppercase"></span>
                </div>
                <div class="mt-3 font-mono text-4xl font-semibold tabular-nums text-zinc-100 sm:text-5xl" id="lane-b-time">—.———</div>
            </div>
        </div>

        <div id="winner-banner" class="hidden rounded-xl border border-zinc-700 bg-zinc-900 px-4 py-3 text-center text-lg font-semibold"></div>

        {{-- START button --}}
        <section class="-mx-4 flex flex-col gap-3 sm:-mx-6">
            <button
                type="button"
                id="btn-start"
                class="w-full rounded-none bg-emerald-600 px-6 py-6 text-3xl font-black tracking-tight text-white shadow-lg shadow-emerald-950/40 transition hover:bg-emerald-500 focus:outline-none focus:ring-4 focus:ring-emerald-400/50 focus:ring-offset-2 focus:ring-offset-zinc-950 disabled:cursor-not-allowed disabled:opacity-40 sm:rounded-2xl sm:py-7 sm:text-4xl md:text-5xl"
            >
                {{ __('START') }}
            </button>
            <label id="countdown-label" class="mx-4 flex w-auto cursor-pointer items-center justify-center gap-3 rounded-xl border border-zinc-700/80 bg-zinc-900/80 px-4 py-3 text-sm text-zinc-300 sm:mx-6">
                <input type="checkbox" id="chk-countdown" class="size-5 shrink-0 rounded border-zinc-600 bg-zinc-800 text-emerald-600 focus:ring-emerald-500" />
                <span>{{ __('3 second countdown + GO') }}</span>
            </label>
        </section>

        <section class="flex flex-col gap-3">
            <div class="flex flex-wrap gap-2">
                <button type="button" id="btn-stop-a" disabled class="min-h-14 min-w-[7rem] flex-1 rounded-xl bg-amber-600 px-5 py-3 text-base font-bold text-white hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-400 disabled:cursor-not-allowed disabled:opacity-40 sm:flex-none sm:text-lg">
                    {{ __('STOP A') }}
                </button>
                <button type="button" id="btn-stop-b" disabled class="min-h-14 min-w-[7rem] flex-1 rounded-xl bg-blue-600 px-5 py-3 text-base font-bold text-white hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:cursor-not-allowed disabled:opacity-40 sm:flex-none sm:text-lg">
                    {{ __('STOP B') }}
                </button>
                <button type="button" id="btn-reset" class="min-h-14 min-w-[7rem] rounded-xl border border-zinc-600 bg-zinc-800 px-5 py-3 text-base font-bold text-zinc-200 hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 sm:text-lg">
                    {{ __('RESET') }}
                </button>
            </div>
            <p class="text-xs text-zinc-500">
                {{ __('Shortcuts') }}: <kbd class="rounded border border-zinc-600 bg-zinc-800 px-1">Space</kbd> {{ __('start') }},
                <kbd class="rounded border border-zinc-600 bg-zinc-800 px-1">A</kbd> {{ __('stop A') }},
                <kbd class="rounded border border-zinc-600 bg-zinc-800 px-1">L</kbd> {{ __('stop B') }},
                <kbd class="rounded border border-zinc-600 bg-zinc-800 px-1">R</kbd> {{ __('reset') }}
            </p>
        </section>

        <section>
            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-medium uppercase tracking-wide text-zinc-500">{{ __('Race log') }}</h2>
                <button
                    type="button"
                    id="btn-clear-history"
                    class="rounded-lg border border-zinc-600 bg-zinc-800/80 px-3 py-1.5 text-xs font-semibold text-zinc-300 hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500"
                >
                    {{ __('Clear log') }}
                </button>
            </div>
            <ul id="history-list" class="space-y-1.5 font-mono text-sm text-zinc-400"></ul>
        </section>

        <p id="broadcast-hint" class="hidden text-xs text-amber-400/90"></p>
    </div>
@endsection
