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
<div class="header-info-pagos-recaudos d-flex flex-wrap justify-content-end align-items-center gap-2 text-end">
  <span class="fw-bold small">CAJA ACTIVA</span>
  <span>
    <small>Sucursal:</small> <span class="fw-bold small">{{ $cajaActiva->nomsucursal }}</span>
  </span>
  <span>
    <small>Fecha apertura:</small> <span class="fw-bold small">{{ \Carbon\Carbon::parse($cajaActiva->fecini)->format('d/m/Y H:i:s') }}</span>
  </span>
</div>
@endsection

@section('content')
<div class="container-fluid py-4">
  <div class="row justify-content-center">
    <div class="col-lg-8 col-xl-6">

      {{-- Card principal --}}
      <div class="card border sidebar-dark-primary rounded-3">
        {{-- Encabezado --}}
        <div class="card-header d-flex bg-primary-subtle justify-content-between align-items-center">
          <h5 class="mb-0 text-light">Confirmación de Pago</h5>
        </div>

        <div class="d-flex flex-wrap gap-2 ms-4 mt-3">
          <a style="width: 150px;" class="btn btn-success fw-bold" href="{{ route('pagosConvenios.index') }}">Regresar</a>
          <a
            style="width: 170px;"
            class="btn btn-outline-primary fw-bold"
            href="{{ route('pagosConvenios.historialHoy') }}"
            onclick="document.getElementById('fullscreen-loader')?.style.setProperty('display', 'flex');">
            Pagos del dia
          </a>
        </div>

        {{-- Contenido --}}
        <div class="card-body text-center py-5">
          <i class="bi bi-check-circle-fill text-success display-3 mb-3"></i>
          <h4 class="fw-bold text-success">¡Operación realizada con éxito!</h4>
          <p class="text-muted mb-2">El pago se ha procesado correctamente.</p>

          <div class="alert alert-light border fw-semibold my-4">
            Número de comprobante: <span>{{ $comprobante->comprobante }}</span>
          </div>

          <a href="{{ route('pagosConvenios.recibo' , ['IdDetallePago' => $idPagoDetalle]) }}"
            class="btn btn-danger btn-lg d-flex justify-content-center align-items-center gap-2 mx-auto"
            target="_blank" style="max-width: 320px;">
            <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
            Descargar recibo PDF
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
