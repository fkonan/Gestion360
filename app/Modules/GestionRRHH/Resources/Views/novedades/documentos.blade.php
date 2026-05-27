@extends('layouts.dashboard')

@section('title', 'Adjuntos del permiso')

@php
  $returnUrl = trim((string) request()->query('return_to', ''));
  $returnLabel = trim((string) request()->query('return_label', ''));

  if ($returnUrl === '') {
      $returnUrl = url()->previous();
  }

  if ($returnLabel === '') {
      $returnLabel = 'Permisos';
  }

  $breadcrumbItems = [
      ['name' => 'Inicio', 'url' => route('home')],
  ];

  if ($returnUrl !== '') {
      $breadcrumbItems[] = ['name' => $returnLabel, 'url' => $returnUrl];
  }

  $breadcrumbItems[] = ['name' => 'Adjuntos del permiso'];
@endphp

@section('breadcrumb')
<x-breadcrumb :items="$breadcrumbItems" />
@endsection

@section('content')
<div class="container-fluid tableContainer p-0 border rounded sidebar-dark-primary" style="min-height:150px">
  <x-sectionHeader
    titulo="Adjuntos del permiso"
    rutaVolver="{{ $returnUrl }}"
    :clasePosition="false" />

  <div class="p-3">
    <div class="mb-2">
      <small class="text-muted">ID novedad: {{ $idNovedad }}</small>
    </div>

    <div id="no-more-tables" class="table-responsive">
      <table class="table table-sm table-striped table-hover align-middle">
        <thead class="table-primary">
          <tr>
            <th>ID documento</th>
            <th>Tipo</th>
            <th>Fecha registro</th>
            <th class="text-center">Acción</th>
          </tr>
        </thead>
        <tbody>
          @forelse($adjuntos as $adjunto)
          <tr>
            <td class="text-nowrap">{{ $adjunto->id }}</td>
            <td class="text-nowrap">{{ $adjunto->tipo_documento }}</td>
            <td class="text-nowrap">{{ $adjunto->fecha_creacion ? \Carbon\Carbon::parse($adjunto->fecha_creacion)->format('d/m/Y H:i') : 'N/A' }}</td>
            <td class="text-center">
              <a
                class="permiso-action-trigger d-inline-flex align-items-center justify-content-center p-0 border-0 bg-transparent text-decoration-none"
                href="{{ route('gestionRRHH.permisos.documentos.ver', ['idDocumento' => $adjunto->id]) }}"
                data-bs-toggle="tooltip"
                data-bs-placement="top"
                title="Ver adjunto"
                target="_blank">
                <img src="{{ asset('img/verPDF.png') }}" alt="Ver adjunto" class="permiso-action-icon">
              </a>
            </td>
          </tr>
          @empty
          <tr>
            <td colspan="4" class="text-center py-3">No hay adjuntos registrados para este permiso.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection

@pushOnce('css')
@vite('resources/css/gestionrrhh/novedades-documentos.css')
@endPushOnce

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (!window.bootstrap || !window.bootstrap.Tooltip) {
      return;
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(elemento) {
      if (typeof window.bootstrap.Tooltip.getOrCreateInstance === 'function') {
        window.bootstrap.Tooltip.getOrCreateInstance(elemento);
        return;
      }

      const instancia = window.bootstrap.Tooltip.getInstance(elemento);
      if (instancia) {
        instancia.dispose();
      }

      new window.bootstrap.Tooltip(elemento);
    });
  });
</script>
@endPushOnce
