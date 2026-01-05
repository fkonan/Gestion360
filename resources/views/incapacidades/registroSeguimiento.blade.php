@extends('layouts.dashboard')

@section('title','Registro Seguimiento')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Empleado', 'url' => route('gestion-incapacidades.index')],
        ['name' => 'Seguimiento Incapacidades', 'url' => route('gestion-empleado.seguimiento')],
        ['name' => 'Registro Seguimiento']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary" style="min-height:150px">

  <x-sectionHeader
    titulo="Registro Seguimiento"
    :crear="true"
    rutaVolver="{{ route('gestion-empleado.seguimiento') }}"
    crearRoute="{{ route('gestion-empleado.seguimiento.detalle.crear', ['id' => $incapacidad->IdIncapacidad]) }}"
    crearLabel="Nuevo Seguimiento"
    crearModalTarget="#formNuevoSeguimiento"
    modalSize="modal-lg" />

  <div style="padding:1.5em">
    <table
      class="table table-bordered align-middle"
      data-page-size="10"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-pagination="true"
      data-detail-formatter="detalleIncapacidad"
      data-mobile-responsive="true"
      data-check-on-init="true"
      data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">
      <thead class="table-primary">
        <tr class="bg-primary">
          <th>Observacion</th>
          <th data-sortable="true">Fecha Registro</th>
          <th>Hora Registro</th>
          <th>Usuario Registro</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody>
        @foreach($listaSeguimiento as $item)
        <tr>
          <td>{{$item->Observacion}}</td>
          <td>{{$item->SegFecReg}}</td>
          <td>{{$item->SegHorReg}}</td>
          <td>{{$item->UserRegistra}}</td>
          <td>
            <span class="badge {{ $item->Estado == 'ACTIVO' ? 'bg-success' : 'bg-danger' }}">
              {{$item->Estado}}
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
@endpushOnce
