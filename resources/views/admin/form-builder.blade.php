@extends('admin.layouts.app')

@section('title', 'Form Builder')

@section('content')
    @livewire('admin.form-builder', isset($packageId) ? ['packageId' => $packageId] : [])
@endsection

