@extends('layouts.app') 

@section('title', 'Tracking Resultado') 

@section('content')
<div class="container mt-5">
    <div class="card mb-5">
        <div class="card-body">
            <a style="width: 200px;" class="btn btn-danger fw-bold ms-4" href="{{ route('trackingRemesas.index') }}">Nueva consulta</a>

            <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
                <table
                    id="trackingRemesas"
                    class="table table-sm table-striped"
                    data-page-size="25"
                    data-detail-formatter="detalleRemesa"
                    data-toggle="table"
                    data-locale="es-ES"> 
                    <thead class="table-primary">
                        <tr>
                            <th>Remesa</th>
                            <th>Bodega origen</th>
                            <th>Fecha recepción envío</th>    
                            <th>Ciudad destino</th>
                            <th>Fecha llegada destino</th>
                            <th>Lugar de entrega</th>
                            <th>Fecha novedad</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($datos as $remesa)
                            <tr>
                                <td>{{ $remesa->remesa ? $remesa->remesa : $remesa->descripcion}}</td>
                                <td>{{ $remesa->nombreorigen }}</td>
                                <td>{{ strtoupper(\Carbon\Carbon::parse($remesa->fecelabora)->format('d-M-y')) }}</td>
                                <td>{{ $remesa->nombredestino }}</td>
                                <td>{{ $remesa->feccumple ? strtoupper(\Carbon\Carbon::parse($remesa->feccumple)->format('d-M-y')) : '' }}</td>
                                <td>{{ $remesa->feccumple ?  "BODEGA-TERMINAL EN " . $remesa->nombredestino : "EN TRANSITO"}}</td>
                                <td>{{ $remesa->descripcion }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div align="center" class="mt-4">
        <table class="table" style="width:85%;">
            <tr>
                @foreach($pasos as $paso)
                    <td align="center" style="border:none;">
                        <img style="width:8%; height:8%;" src="{{ $paso['img'] }}{{ $paso['activo'] ? 'green' : 'gray' }}.png"><br>
                        <i class="{{ $paso['icon'] }}"></i>&#160;{{ $paso['label'] }}
                    </td>
                @endforeach
            </tr>
        </table>
    </div>
</div>
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            initTablaBootstrapTable(
                '#trackingRemesas', 
                { protegidas: [''] }, 
                'detalleRemesa', 
            );
        });
    </script>
@endpushOnce