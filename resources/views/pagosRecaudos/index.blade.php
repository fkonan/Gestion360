@extends('layouts.dashboard')

@section('title','Pagos y recaudos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Pago convenio']
    ]" />
<br>
@endsection

@section('headerInfo')
    <div class="row me-3">
        <div class="col-12">
            <span>
                <span class="fw-bold small">CAJA ACTIVA</span>
                &nbsp;&nbsp;&nbsp;
                <small>Sucursal:</small> <span class="fw-bold small">{{ $cajaActiva[0]->nomsucursal }}</span>
                &nbsp;&nbsp;&nbsp;
                <small>Fecha apertura:</small> <span class="fw-bold small">{{ \Carbon\Carbon::parse($cajaActiva[0]->fecini)->format('d/m/Y H:i:s') }}</span>
            </span>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer pb-3" style="min-height:150px">

    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-primary-subtle">
        <span class="text-left text-light fs-5 fw-medium py-1">Registrar pago o recaudo</span>
    </div>

    <div class="ms-4 mt-3">
        <a style="width: 150px;" class="btn btn-success fw-bold" href="{{ route('home') }}">Regresar</a>
    </div>

    <form 
        id="formPago" 
        action="{{ route('pagosConvenios.consultar') }}" 
        method="POST" 
        class="p-4" 
        onsubmit="deshabilitarSubmit(this)">
        @csrf

        <div class="row">
             <div class="col-12 col-md-5">
                <label for="empresa" class="form-label">Seleccione empresa</label>
                <select class="form-select" id="empresa" name="empresa" required>
                    <option value="CAJASAN" selected>CAJASAN</option>
                </select>
            </div>

            <div class="col-12 col-md-5">
                <label for="identificacion" class="form-label">Ingrese la identificación del cliente</label>
                <input type="text" class="form-control" id="identificacion" name="identificacion" placeholder="Ej: 1234567890" required>
            </div>

            <div class="col-12 col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Consultar</button>
            </div>
        </div>
    </form>
</div>

 <!-- HTML por peticion AJAX del formulario-->
<div id="innerHtml"></div>
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function () {
            validarFormulario("#formPago");
            $('.select2').select2({
                dropdownParent: $("#formPago"),
                width: '100%'
            });
        });
    </script>
@endpushOnce

