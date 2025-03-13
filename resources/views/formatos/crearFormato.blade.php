@extends('layouts.dashboard')

@section('title','Nuevo Formato')

@section('content')
<div class="container-fluid p-0 bg-white border rounded">
    <div class="border rounded-top d-flex justify-content-between align-items-center" style="background-color: #2C3643">
        <h5 class="text-left text-light p-2 ps-4">Nuevo proceso</h5>
        <a class="btn btn-warning fw-bold m-2" onclick="window.history.back()" >Volver</a>
    </div>

    <form id="formFormato" action="{{ route('formatos.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row g-3 p-4">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="FormCod" class="form-label">Código</label>
                <input type="text" class="form-control" id="FormCod" name="FormCod" required>
                <span class="error text-danger fw-bold" id="error-FormCod"></span>
            </div>
            <div class="mb-3">
                <label for="FormNom" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="FormNom" name="FormNom" required>
                <span class="error text-danger fw-bold" id="error-FormNom"></span>
            </div>
            <div class="mb-3">
                <label for="FormTipProc" class="form-label">Tipo proceso</label>
                <select class="form-select" id="FormTipProc" name="FormTipProc" required>
                    <option value="">Seleccione un tipo de proceso</option>
                    @foreach($tipoProcesos as $tp)
                        <option value="{{$tp->Id}}">{{$tp->Nombre}}</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-FormTipProc"></span>
            </div>
            <div class="mb-3">
                <label for="FormTipDoc" class="form-label">Tipo documento</label>
                <select class="form-select" class="form-control" id="FormTipDoc" name="FormTipDoc" required>
                    <option value="">Seleccione un tipo de documento</option>
                    @foreach($tipoDocProcesos as $td)
                        <option value="{{$td->Id}}">{{$td->Nombre}}</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-FormTipDoc"></span>
            </div>
            <div class="mb-3">
                <label for="pdf" class="form-label">Archivo PDF</label>
                <input type="file" accept="application/pdf" class="form-control" id="pdf" name="pdf" required>
                <span class="error text-danger fw-bold" id="error-pdf"></span>
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
                <label for="FormUbicacion" class="form-label">Ubicación</label>
                <input type="text" class="form-control" id="FormUbicacion" name="FormUbicacion" required>
                <span class="error text-danger fw-bold" id="error-FormUbicacion"></span>
            </div>
        </div>
    </div>

    <div class="text-start ms-4 mb-4">
        <button type="submit" class="btn btn-primary">Guardar</button>
    </div>
    </form>
</div>
@endsection

@section('script')
    <script>
        $(document).ready(function () {
            validarFormulario("#formFormato");
        });
    </script>
@endsection
