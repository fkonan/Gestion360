@php use Carbon\Carbon; @endphp

<div class="mb-3 px-3 pt-2">
  <h6 class="mb-1">Emisiones del documento</h6>
  <p class="text-muted mb-0">
    <span class="text-primary fw-bold">{{ $documento->codigo }}</span> - {{ $documento->nombre }}
    @if($documento->proceso)
      <span class="d-block small">Proceso: {{ $documento->proceso->nombre }}</span>
    @endif
  </p>
</div>

<div class="table-responsive px-3 pb-3" style="max-height: 65vh; overflow-y: auto;">
  <table
    id="tablaEmisiones"
    class="table table-sm table-striped table-hover align-middle mb-0"
    data-toggle="table"
    data-detail-view="true"
    data-detail-formatter="detalleDocumentos">
    <thead class="table-primary text-center">
      <tr>
        <th class="text-nowrap text-start">Emision</th>
        <th class="text-nowrap text-start">Comentario</th>
        <th class="text-nowrap text-start">Elaboro</th>
       <!--  <th class="text-nowrap text-start">Reviso</th>
        <th class="text-nowrap text-start">Aprueba</th> -->
        <th class="text-nowrap text-start">Fecha aprobacion</th>
        <th class="text-nowrap text-center">Acciones</th>
      </tr>
    </thead>
    <tbody class="align-middle">
      @forelse($versiones as $version)
        <tr>
          <td class="fw-semibold text-center align-middle">
            <span class="badge" style="background-color: #005eb8;">{{ $version->version }}</span>
          </td>
          <td class="text-start align-middle">{{ $version->comentario_revision ? \Illuminate\Support\Str::ucfirst(mb_strtolower($version->comentario_revision, 'UTF-8')) : '—' }}</td>
          <td class="text-start align-middle">{{ $version->elaboro_nombre ? \Illuminate\Support\Str::ucfirst(mb_strtolower($version->elaboro_nombre, 'UTF-8')) : ($version->id_elabora ?? '—') }}</td>
          <!-- <td class="text-start align-middle">{{ $version->reviso_nombre ? \Illuminate\Support\Str::ucfirst(mb_strtolower($version->reviso_nombre, 'UTF-8')) : ($version->id_revisa ?? '—') }}</td>
          <td class="text-start align-middle">{{ $version->aprueba_nombre ? \Illuminate\Support\Str::ucfirst(mb_strtolower($version->aprueba_nombre, 'UTF-8')) : ($version->id_aprueba ?? '—') }}</td> -->
          <td class="text-start align-middle">
            @if($version->fecha_aprobacion)
              {{ Carbon::parse($version->fecha_aprobacion)->format('Y-m-d') }}
            @else
              —
            @endif
          </td>
          <td class="text-center align-middle">
            <button type="button" class="btn btn-link p-0" title="Ver PDF (proximamente)" disabled>
              <img src="{{ asset('img/verPDF.png') }}" alt="PDF" style="width: 24px; height: 24px;">
            </button>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="text-center text-muted align-middle">Sin emisiones registradas.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    initTablaBootstrapTable('#tablaEmisiones', { protegidas: ['version'] }, 'detalleDocumentos', {
      comentario_revision: (val) => val ?? '',
      elaboro_nombre: (val) => val ?? '',
      fecha_aprobacion: (val) => val ?? '',
    });
  });
</script>
