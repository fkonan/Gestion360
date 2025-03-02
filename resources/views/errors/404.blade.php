@extends('layouts.app')

@section('title','Error 404')

@section('content')
<div class="container text-center mt-5">
    <h1>404 - Página no encontrada</h1>
    <p>La URL solicitada no existe. Por favor, verifica la dirección e intenta de nuevo.</p>
    <a href="{{ route('home') }}" class="btn btn-primary">Volver al inicio</a>
</div>
@endsection
