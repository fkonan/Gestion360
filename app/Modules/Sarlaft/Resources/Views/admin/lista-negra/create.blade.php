@extends('layouts.dashboard')

@section('title', 'Agregar a Lista Restrictiva')

@section('breadcrumb')
   <x-breadcrumb :items="[
       ['name' => 'Inicio', 'url' => route('home')],
       ['name' => 'Sarlaft', 'url' => '#'],
       ['name' => 'Lista Restrictiva', 'url' => route('sarlaft.lista-negra.index')],
       ['name' => 'Nuevo Registro'],
   ]" />
   <br>
@endsection

@section('content')
   <div class="card shadow-sm">
      <x-sectionHeader titulo="Listas Restrictivas - Nuevo Registro" rutaVolver="{{ route('sarlaft.lista-negra.index') }}"
         btnVolver=false />
      <div class="card-header d-flex justify-content-between align-items-center">
         <a href="{{ route('sarlaft.lista-negra.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left"></i> Regresar
         </a>
      </div>
      <div class="card-body">
         <form action="{{ route('sarlaft.lista-negra.store') }}" method="POST">
            @csrf
            @include('sarlaft::admin.lista-negra._form')
            <button type="submit" class="btn btn-success">
               Guardar
            </button>
         </form>
      </div>
   </div>
@endsection
