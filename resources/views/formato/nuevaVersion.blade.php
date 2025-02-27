@extends('layouts.app')

@section('title','Nueva Version')

@section('content')
<div class="container mt-4">
    <h1 class="text-center mb-4">Nueva Version</h1>

    <form id="formNuevaVersion" action="{{ route('formato.guardarVersionFormato') }}" method="POST" enctype="multipart/form-data">
        
    @csrf
    <div class="row g-3">
        <div class="col-md-6">
            <input type="hidden" name="IdFormato" value="{{ $formato->IdFormato }}">
            <input type="hidden" name="FormNom" value="{{ $formato->FormNom }}">

            <div class="mb-3">
                <label for="FormCod" class="form-label">Código</label>
                <input type="text" class="form-control" id="FormCod" name="FormCod" value="{{ $formato->FormCod }}" disabled>
            </div>
            <div class="mb-3">
                <label for="FormNom" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="FormNom" name="FormNom" value="{{ $formato->FormNom }}" disabled>
            </div>
            <div class="mb-3">
                <label for="FormTipo" class="form-label">Tipo</label>
                <input type="text" class="form-control" id="FormTipo" name="FormTipo" value="{{ $formato->FormTipo }}" disabled>
            </div>
            <div class="mb-3">
                <label for="FormUbicacion" class="form-label">Ubicación</label>
                <input type="text" class="form-control" id="FormUbicacion" name="FormUbicacion" value="{{ $formato->FormUbicacion }}" disabled>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="mb-3">
                <label for="VerElaboro" class="form-label">Elaboró</label>
                <input type="text" class="form-control" id="VerElaboro" name="VerElaboro" required>
                <span class="error text-danger fw-bold" id="error-VerElaboro"></span>
            </div>
            <div class="mb-3">
                <label for="VerReviso" class="form-label">Revisó</label>
                <input type="text" class="form-control" id="VerReviso" name="VerReviso" required>
                <span class="error text-danger fw-bold" id="error-VerReviso"></span>
            </div>
            <div class="mb-3">
                <label for="VerAprobo" class="form-label">Aprobó</label>
                <input type="text" class="form-control" id="VerAprobo" name="VerAprobo" required>
                <span class="error text-danger fw-bold" id="error-VerAprobo"></span>
            </div>
            <div class="mb-3">
                <label for="pdf" class="form-label">Archivo PDF</label>
                <input type="file" accept="application/pdf" class="form-control" id="pdf" name="pdf" required>
                <span class="error text-danger fw-bold" id="error-pdf"></span>
            </div>
        </div>
    </div>

    <div class="text-end mt-3">
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
    </form>
</div>
@endsection

@section('script')
<script>
    $(document).ready(function () {
        validarFormulario("#formNuevaVersion");
    });
    
</script>
@endsection
