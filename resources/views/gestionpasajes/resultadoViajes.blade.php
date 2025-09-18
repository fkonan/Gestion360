@extends('layouts.dashboard')

@section('title','Viajes disponibles')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Buscar viaje', 'url' => route('buscar-viaje.index')],
        ['name' => 'Viajes disponibles']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 sidebar-dark-primary border rounded" style="min-height:150px;">

  <div class="border bg-primary-subtle rounded-top d-flex justify-content-between align-items-center px-4">
    <span class="text-left text-light fs-5 fw-medium py-1">Viajes disponibles</span>
    <a class="btn btn-success fw-bold my-2" href="{{ route('buscar-viaje.index') }}">Regresar</a>
  </div>

  <div id="no-more-tables" class="table-responsive" style="padding:1.5em">
    <table
      id="viajesDataTable"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-pagination="true"
      data-check-on-init="true">
      <thead class="table-primary">
        <tr>
          <th>Número de Viaje</th>
          <th>Origen</th>
          <th>Destino</th>
          <th data-sortable="true">Servicio</th>
          <th class="text-center" data-sortable="true">Fecha de Salida</th>
          <th>Hora</th>
          <!--  <th>Precio</th> -->
        </tr>
      </thead>
      <tbody>
        @foreach($resultados as $viaje)
        <tr>
          <td>{{ $viaje->Viaje }}</td>
          <td>{{ $viaje->TerminalOrigen }}</td>
          <td>{{ $viaje->TerminalDestino }}</td>
          <td>{{ $viaje->Servicio }}</td>
          <td>{{ \Carbon\Carbon::parse($viaje->FechaPartida)->toDateString() }}</td>
          <td>{{ \Carbon\Carbon::parse($viaje->FechaPartida)->format('g:i A') }}</td>
          <!--  <td>{{ number_format($viaje->Precio, 0) }}</td> -->
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
