@extends('layouts.dashboard')

@section('title','Home')

@section('content')
<div class="container text-center">
    @if(auth()->check())
        <h1>Bienvenido {{ auth()->user()->persona->PerNombres }} , {{ auth()->user()->persona->PerApellidos }}</h1>
    @else
        <h1>Bienvenido</h1>
    @endif
</div>
@endsection

