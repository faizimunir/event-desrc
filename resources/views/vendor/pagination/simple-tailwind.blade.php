@php
$btnBase = 'inline-flex items-center px-4 py-2 text-sm font-medium leading-5 rounded-xl transition ease-in-out duration-150 focus:outline-none focus:ring-2 focus:ring-orange-500/30 focus:border-orange-400';
$btnIdle = 'text-zinc-700 bg-white border border-zinc-200 hover:bg-zinc-50 hover:text-zinc-900 dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white';
$btnDisabled = 'text-zinc-400 bg-white border border-zinc-200 cursor-default dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-600';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between gap-3">
        @if ($paginator->onFirstPage())
            <span class="{{ $btnBase }} {{ $btnDisabled }}">
                {!! __('pagination.previous') !!}
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $btnBase }} {{ $btnIdle }}">
                {!! __('pagination.previous') !!}
            </a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $btnBase }} {{ $btnIdle }}">
                {!! __('pagination.next') !!}
            </a>
        @else
            <span class="{{ $btnBase }} {{ $btnDisabled }}">
                {!! __('pagination.next') !!}
            </span>
        @endif
    </nav>
@endif
