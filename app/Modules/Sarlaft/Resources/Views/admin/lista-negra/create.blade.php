@extends('layouts.dashboard')

@section('title', 'Agregar a Lista Negra')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-user-plus"></i> Nuevo Registro</h6>
                <a href="{{ route('sarlaft.lista-negra.index') }}" class="btn btn-sm btn-dark">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.lista-negra.store') }}" method="POST">
                    @csrf
                    @include('sarlaft::admin.lista-negra._form')
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Agregar a Lista Negra
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
