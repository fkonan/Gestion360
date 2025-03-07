@extends('layouts.dashboard')

@section('title','Lista modulos')

@section('content')
<div class="container-fluid p-0 bg-white border rounded">

    <div class="border rounded-top d-flex justify-content-between align-items-center" style="background-color: #2C3643">
        <h5 class="text-left text-light p-2 ps-4">Modulos</h5>
        <a class="btn btn-warning fw-bold m-2" onclick="window.history.back()" >Volver</a>
    </div>

    <a class="btn btn-warning fw-bold ms-4 mt-4" style="position: absolute; top:150px" href="{{ route('configuracion.sistema.modulos.create') }}">Crear Modulo</a>

    <div class="row p-4">
        <table
            class="table table-striped"
            data-toggle="table"
            data-search="true"
            data-pagination="true"
            data-page-size="10"
            data-page-list="[]"
            data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                    <th>Estado</th>
                    <th>Modulo Padre</th>    
                    <th>Fecha Registro</th>
                    <th>Hora Registro</th>
                    <th>Opciones</th>
                </tr>
            </thead>
            <tbody>
            @foreach($modulos as $modulo)
                <tr>
                   <th>{{ $modulo->ModNom}}</th>
                   <th>{{ $modulo->ModDesc}}</th>
                   <th>{{ $modulo->ModEstado}}</th>
                   <th>{{ $modulo->padre->ModNom ?? "PRINCIPAL" }}</th>
                   <th>{{ $modulo->ModFechReg}}</th>
                   <th>{{ $modulo->ModHorReg}}</th>
                   <td class="text-center" style="width: 80px;">
                        <a class="btn btn-primary ">
                            <i class="nav-icon fas fa-edit"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection