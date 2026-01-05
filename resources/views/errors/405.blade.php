@extends('layouts.app')

@section('title','Error 405')

@section('content')
<div class="container text-center mt-5">
  <h1>Error 405 - Método no permitido</h1>
  <p>No puedes acceder a esta ruta con el método {{ request()->method() }}.</p>
  <a href="{{ route('index') }}" class="btn btn-primary">Volver al inicio</a>
</div>
@endsection
