@extends('layouts.bento-public')

@section('title')
    {{ __('Live Result') }}
@endsection

@section('content')
    <div class="bento-card bento-section-shell">
        <livewire:live-result-events-section :show-view-all="false" />
    </div>
@endsection
