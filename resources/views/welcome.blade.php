@extends('layouts.app')

@section('title','Welcome')

@section('content')
<div>
    @if(auth()->check())
        <h1>Bienvenido {{ auth()->user()->persona->PerNombres }} , {{ auth()->user()->persona->PerApellidos }}</h1>
        <form action="{{ route('logout') }}" method="POST" style="display:inline">
            @csrf
            <button type="submit" class="btn btn-danger">Cerrar sesión</button>
        </form>
    @else
        <h1>Bienvenido</h1>
    @endif
</div>
@endsection

