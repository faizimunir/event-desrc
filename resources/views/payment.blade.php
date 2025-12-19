@extends('layouts.app')

@section('title', 'Pembayaran')

@section('content')
    @livewire('payment', ['participantId' => $participantId])
@endsection

