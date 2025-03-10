@extends('layouts.dashboard')

@section('title','Nuevo Modulo')

@section('content')
<div class="container-fluid p-0 bg-white border rounded">
    <div class="border rounded-top d-flex justify-content-between align-items-center" style="background-color: #2C3643">
        <h5 class="text-left text-light p-2 ps-4">Nuevo Modulo</h5>
        <a class="btn btn-warning fw-bold m-2" onclick="window.history.back()">Volver</a>
    </div>

    <form id="formFormato" action="{{ route('modulos.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-3 p-4">       
            <div class="col-md-3">
                <label for="ModNom" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="ModNom" name="ModNom" maxlength="100" required>
                <span class="error text-danger fw-bold" id="error-ModNom"></span>
            </div>

            <div class="col-md-2">
                <label for="Mod_Padre_Id" class="form-label">Modulo Padre</label>
                <select class="form-select" id="Mod_Padre_Id" name="Mod_Padre_Id" placeholder="Seleccione si es parte de otro modulo">
                    <option value="">Modulo Principal</option>
                    @foreach($modulos as $modulo)
                        <option value="{{ $modulo->IdModulo }}">{{ $modulo->ModNom }}</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-Mod_Padre_Id"></span>
            </div>

            <div class="col-md-2">
                <label for="ModEstado" class="form-label">Estado</label>
                <select class="form-select" id="ModEstado" name="ModEstado" required>
                    <option value="">Seleccione</option>
                    <option value="ACTIVO">Activo</option>
                    <option value="INACTIVO">Inactivo</option>
                    <option value="ELIMINADO">Eliminado</option>
                </select>
                <span class="error text-danger fw-bold" id="error-ModEstado"></span>
            </div>   

            <div class="col-md-3">
                <label for="ModRuta" class="form-label">Ruta</label>
                <input type="text" class="form-control" id="ModRuta" name="ModRuta">
                <span class="error text-danger fw-bold" id="error-ModRuta"></span>
            </div>

            <div class="col-md-2">
                <label for="ModIcono" class="form-label">Icono</label>
                <input type="text" class="form-control" id="ModIcono" name="ModIcono">
                <span class="error text-danger fw-bold" id="error-ModIcono"></span>
            </div>
        </div>

        <div class="row gx-3 px-4">
            <div class="mb-3">
                <label for="comentario" class="form-label">Descripción</label>
                <textarea class="form-control" id="ModDesc" name="ModDesc" maxlength="300" rows="5"></textarea>
                <div class="form-text">Máximo 300 caracteres.</div>
                <div id="contador" class="text-end text-muted" style="font-size: 0.875rem;">0/300</div>
                <span class="error text-danger fw-bold" id="error-ModDesc"></span>
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

        const comentario = document.getElementById('ModDesc');
        const contador = document.getElementById('contador');

        comentario.addEventListener('input', () => {
            contador.textContent = `${comentario.value.length}/300`;
        });
    </script>
@endsection
