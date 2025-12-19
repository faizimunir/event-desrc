@extends('layouts.app')

@section('title', 'Event Detail')

@section('content')
    @livewire('event-detail', ['id' => $id])
@endsection

