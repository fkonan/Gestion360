@extends('layouts.dashboard')

@section('title','Buscar viaje')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Buscar viaje'],
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Buscar Viaje</span>
    </div>

    <form 
        id="formBuscarViaje" 
        action="{{ route('buscar-viaje.filtrar') }}" 
        method="POST" enctype="multipart/form-data"
        onsubmit="deshabilitarSubmit(this)">

        @csrf
        <div class="row g-3 p-4">       
            <div class="col-md-4">
                <label for="origen" class="form-label">Origen del viaje *</label>
                <select class="form-select select2" id="origen" name="origen" required>
                    <option value="" selected disabled>Seleccione un origen</option>
                    @foreach($municipios as $municipio)
                        <option value="{{ $municipio->MunNom }}">{{ $municipio->MunNom }} - ({{ $municipio->departamento->Abreviacion }})</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-origen"></span>
            </div>

            <div class="col-md-4">
                <label for="destino" class="form-label">Destino del viaje *</label>
                <select class="form-select select2" id="destino" name="destino" required>
                    <option value="" selected disabled>Seleccione un destino</option>
                    @foreach($municipios as $municipio)
                        <option value="{{ $municipio->MunNom }}">{{ $municipio->MunNom }} - ({{ $municipio->departamento->Abreviacion }})</option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-destino"></span>
            </div>
        </div> 

        <div class="text-start ms-4 mb-4">
            <button type="submit" class="btn btn-success">Buscar</button>
        </div>
    </form>
</div>
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            validarFormulario("#formBuscarViaje");
            $('.select2').select2({
                dropdownParent: $("#formBuscarViaje"),
                width: '100%'
            });
        });
    </script>
@endpushOnce

