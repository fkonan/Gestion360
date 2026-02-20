@extends('layouts.dashboard')

@section('title', 'Editar Registro Lista Negra')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="fas fa-user-edit"></i> Editar Registro</h6>
                <a href="{{ route('sarlaft.lista-negra.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.lista-negra.update', $listaNegra) }}" method="POST">
                    @csrf
                    @method('PUT')
                    @include('sarlaft::admin.lista-negra._form', ['registro' => $listaNegra])
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> Actualizar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
