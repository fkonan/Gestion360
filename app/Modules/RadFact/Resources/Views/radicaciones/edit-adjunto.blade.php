@extends('layouts.dashboard')

@section('title', 'Cargar Adjunto de Radicación')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Radicaciones', 'url' => route('radfact.radicaciones.index')],
        ['name' => 'Ver radicación', 'url' => route('radfact.radicaciones.show', $radicacion)],
        ['name' => 'Cargar adjunto'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border shadow rounded sidebar-dark-primary">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
        <span class="text-left text-light fs-5 fw-medium">Cargar PDF de factura</span>
    </div>

    <form action="{{ route('radfact.radicaciones.update_adjunto', $radicacion) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row pt-4 mx-4">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">N° Radicación</label>
                <input type="text" class="form-control" value="#{{ str_pad($radicacion->id, 6, '0', STR_PAD_LEFT) }}" disabled>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">N° Factura</label>
                <input type="text" class="form-control" value="{{ $radicacion->num_factura }}" disabled>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Proveedor</label>
                <input type="text" class="form-control" value="{{ $radicacion->proveedor->nombre_completo ?? 'N/A' }}" disabled>
            </div>
        </div>

        <div class="row mx-4 pb-4">
            <div class="col-md-6 mb-3">
                <label for="pdf" class="form-label">PDF de Factura *</label>
                <input type="file" class="form-control @error('pdf') is-invalid @enderror" id="pdf" name="pdf" accept=".pdf" required>
                <span class="error text-danger fw-bold" id="error-pdf">
                    @error('pdf')
                    {{ $message }}
                    @enderror
                </span>
                <small class="text-muted d-block mt-1">Tamaño máximo permitido: 10MB.</small>
            </div>
        </div>

        <div class="p-4">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-upload"></i> Cargar Adjunto
            </button>
            <a href="{{ route('radfact.radicaciones.show', $radicacion) }}" class="btn btn-dark">
                <i class="fas fa-times"></i> Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
