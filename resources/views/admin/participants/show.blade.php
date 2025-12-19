@extends('admin.layouts.app')

@section('title', 'Detail Registrasi Peserta')

@section('content')
    <livewire:admin.participant-detail :id="$id" />
@endsection

