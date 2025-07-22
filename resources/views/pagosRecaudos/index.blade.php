@extends('layouts.dashboard')

@section('title','Pagos y recaudos')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Pago convenio']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px">

    <x-sectionHeader 
        titulo="Registrar pago o recaudo"
        rutaVolver="{{ route('home') }}"
    />

    <form 
        id="formPago" 
        action="{{ route('pagosConvenios.consultar') }}" 
        method="POST" 
        class="p-4" 
        style="margin-top: 80px"
        onsubmit="deshabilitarSubmit(this)">
        @csrf

        <div class="row">
             <div class="col-6">
                <label for="empresa" class="form-label">Seleccione empresa</label>
                <select class="form-select" id="empresa" name="empresa" required>
                    <option value="CAJASAN" selected>CAJASAN</option>
                    <option value="EMPRESA2">EMPRESA2</option>
                    <option value="EMPRESA3">EMPRESA3</option>
                </select>
            </div>

            <div class="col-6">
                <label for="identificacion" class="form-label">Ingrese la identificación del cliente</label>
                <input type="text" class="form-control" id="identificacion" name="identificacion" placeholder="Ej: 1234567890" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary mt-3">Consultar</button>
    </form>
</div>
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
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

