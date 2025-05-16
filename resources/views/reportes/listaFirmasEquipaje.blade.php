@extends('layouts.dashboard')

@section('title','Lista firmas')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Home', 'url' => route('home')],
        ['name' => 'Reportes', 'url' => route('reportes.index')],
        ['name' => 'Firmas equipaje'],
    ]" />
<br>
@endsection
    
@section('content')
<div class="container-fluid p-0 border rounded" style="min-height:150px; background-color: white">
    <div class="border rounded-top d-flex justify-content-between align-items-center px-4 bg-secondary">
        <span class="text-left text-light fs-5 fw-medium py-1">Resultado firma equipaje</span>
    </div>

    <a id="exportar" class="btn fw-bold ms-4 mt-4 bg-success botonBoostrapTable"> Descargar Excel </a>

    <div class="row p-4">
        <table
            class="table table-sm table-striped"
            data-page-size="25"
            data-toggle="table"
            data-locale="es-ES"
            data-search="true"
            data-pagination="true"
            data-mobile-responsive="true"
            data-check-on-init="true"
            data-url="{{ route('firmaEquipaje.cargarData') }}"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead class="table-primary">
                <tr class="bg-primary">
                    <th data-field="NomCon">Nombre y apellidos</th>
                    <th data-field="DocCon">Identificación</th>
                    <th data-field="CodCon">Codigo</th>
                    <th data-field="FirFecReg">Fecha firma</th>
                    <th data-field="FirHorReg">Hora firma</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection 

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
     let btnExportar = document.getElementById("exportar");
        btnExportar.addEventListener("click", function () {         
            let url = "{{ route('firmaEquipaje.cargarData') }}";

            $.ajax({
                url: url,
                method: 'GET',
            
                success: function (data, status, xhr) {
                    let ws = XLSX.utils.json_to_sheet(data);
                    let wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "Datos");
                    XLSX.writeFile(wb, "informe_firma_equipaje.xlsx");
                },
                error: function (xhr, status, error) {
                    alert('Error al exportar los datos. Por favor, intente nuevamente.');
                }
            });
        });
    </script>
@endpushOnce

