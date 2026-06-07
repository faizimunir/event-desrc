@extends('layouts.bento-public')

@section('title')
    {{ __('Events') }}
@endsection

@section('content')
    <div class="bento-card bento-section-shell">
        <livewire:event-cards-section />
    </div>
@endsection
