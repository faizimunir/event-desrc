@extends('layouts.app')

@section('title', 'Registrasi Peserta')

@section('content')
    @livewire('registration', ['packageId' => $packageId, 'categoryId' => $categoryId ?? null])
@endsection

