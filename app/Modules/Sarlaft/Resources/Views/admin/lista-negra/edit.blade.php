@extends('layouts.dashboard')

@section('title', 'Editar Registro Lista Restrictiva')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Sarlaft', 'url' =>'#'],
        ['name' => 'Lista Restrictiva', 'url' => route('sarlaft.lista-negra.index')],
        ['name' => 'Editar Registro']
    ]" />
<br>
@endsection
@section('content')
   <div class="card shadow-sm">
      <x-sectionHeader titulo="Listas Restrictivas - Editar Registro" rutaVolver="{{ route('sarlaft.lista-negra.index') }}" btnVolver=false />
      <div class="card-header d-flex justify-content-between align-items-center">
         <a href="{{ route('sarlaft.lista-negra.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Regresar
         </a>
      </div>
      <div class="card-body">
         <form action="{{ route('sarlaft.lista-negra.update', $listaNegra) }}" method="POST">
            @csrf
            @method('PUT')
            @include('sarlaft::admin.lista-negra._form', ['registro' => $listaNegra])
            <button type="submit" class="btn btn-success">
               <i class="fas fa-check"></i> Actualizar
            </button>
         </form>
      </div>
   </div>
@endsection
