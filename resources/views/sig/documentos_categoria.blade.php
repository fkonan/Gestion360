@extends('layouts.dashboard')

@section('title', $categoriaTitulo)

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'SIG', 'url' => route('home')],
        ['name' => $categoriaTitulo]
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    :titulo="$categoriaTitulo"
    rutaVolver="{{ route('home') }}" />

  <div id="no-more-tables" class="table-responsive" style="padding:1em 1.25em">
    <div class="d-flex justify-content-end mb-2 gap-2">
      @permite(\App\Constants\Permisos::SIG_MAPA_PROCESOS_VER_EMISION)
        <a href="{{ route('mapa-procesos.emisiones.devueltas') }}" class="btn btn-info btn-sm">
          Mis solicitudes
        </a>
      @endpermite
      @permite(\App\Constants\Permisos::SIG_MAPA_PROCESOS_ACCEDER)
      <a href="{{ route('mapa-procesos.emisiones.pendientes') }}" class="btn btn-warning btn-sm">
        Solicitudes pendientes
      </a>
      @endpermite
    </div>

    <div class="row g-3 mt-5">
      <div class="col-12 col-md-4 col-lg-3">
        <label for="filtroProceso" class="form-label mb-1 text-muted small">Filtrar por proceso</label>
        <select id="filtroProceso" class="form-select form-select-sm">
          <option value="">Todos</option>
          @foreach($procesosFiltro as $proceso)
            <option value="{{ $proceso['id'] }}">{{ $proceso['label'] }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-4 col-lg-3">
        <label for="filtroTipo" class="form-label mb-1 text-muted small">Filtrar por tipo de documento</label>
        <select id="filtroTipo" class="form-select form-select-sm">
          <option value="">Todos</option>
          @foreach($tiposFiltro as $tipo)
            <option value="{{ $tipo['id'] }}">{{ $tipo['label'] }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-12 col-md-4 col-lg-3">
        <label for="filtroEstado" class="form-label mb-1 text-muted small">Estado</label>
        <select id="filtroEstado" class="form-select form-select-sm">
          <option value="activos" @if(($estadoFiltro ?? 'activos') === 'activos') selected @endif>Activos</option>
          @permite(\App\Constants\Permisos::SIG_MAPA_PROCESOS_ELIMINAR)
            <option value="inactivos" @if(($estadoFiltro ?? 'activos') === 'inactivos') selected @endif>Inactivos</option>
            <option value="todos" @if(($estadoFiltro ?? 'activos') === 'todos') selected @endif>Todos</option>
          @endpermite
        </select>
      </div>
    </div>

    <table
      id="tablaDocumentos"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-pagination="true"
      data-detail-view="true"
      data-detail-formatter="detalleDocumentos"
      data-check-on-init="true"
      data-data='@json($documentos)'
      data-puede-crear="{{ Auth::user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_CREAR_EMISION) ? '1' : '0' }}"
      data-puede-estado="{{ Auth::user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_ELIMINAR) ? '1' : '0' }}"
      data-puede-ver="{{ Auth::user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_VER_EMISION) ? '1' : '0' }}"
      data-puede-inactivos="{{ Auth::user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_ELIMINAR) ? '1' : '0' }}"
      data-puede-editar="{{ Auth::user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_EDITAR) ? '1' : '0' }}">
      <thead class="table-primary">
        <tr>
          <th data-field="codigo" data-sortable="true" class="text-nowrap">C&oacute;digo</th>
          <th data-field="nombre" data-sortable="true" data-formatter="capitalizarFormatter">Nombre</th>
          <th data-field="version" data-sortable="true" data-align="center" class="text-nowrap">Emisi&oacute;n</th>
          <th data-field="fecha_aprobacion" data-sortable="true" data-formatter="fechaFormatter" class="text-nowrap">Fecha aprobaci&oacute;n</th>
          <th data-field="elaboro" data-sortable="true" data-formatter="capitalizarFormatter">Elabor&oacute;</th>
          <th data-field="reviso" data-sortable="true" data-formatter="capitalizarFormatter">Revis&oacute;</th>
          <th data-field="aprueba" data-sortable="true" data-formatter="capitalizarFormatter">Aprueba</th>
          <th data-field="ubicacion" data-sortable="true" data-formatter="capitalizarFormatter">Ubicaci&oacute;n</th>
          @permite(\App\Constants\Permisos::SIG_MAPA_PROCESOS_ELIMINAR)
            <th data-field="estado" data-formatter="estadoFormatter" data-align="center" class="text-center">Estado</th>
          @endpermite
          <th data-field="acciones" data-formatter="accionesDocumentos" class="text-center">Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  const tablaDocumentos = document.getElementById('tablaDocumentos');
  const sigDocumentosConfig = {
    datosOriginales: tablaDocumentos ? JSON.parse(tablaDocumentos.getAttribute('data-data') || '[]') : [],
    puedeCrearEmision: tablaDocumentos?.dataset.puedeCrear === '1',
    puedeCambiarEstado: tablaDocumentos?.dataset.puedeEstado === '1',
    puedeVerEmisiones: tablaDocumentos?.dataset.puedeVer === '1',
    puedeVerInactivos: tablaDocumentos?.dataset.puedeInactivos === '1',
    puedeEditarDocumento: tablaDocumentos?.dataset.puedeEditar === '1',
    rutas: {
      emisiones: "{{ route('mapa-procesos.documento.emisiones', ['id' => ':id']) }}",
      nueva: "{{ route('mapa-procesos.documento.emision.nueva', ['id' => ':id']) }}",
      editar: "{{ route('mapa-procesos.documento.editar', ['id' => ':id']) }}",
      estado: "{{ route('mapa-procesos.documento.estado', ['id' => ':id']) }}",
      pdf: "{{ asset('storage/pdfs/documento_pruebas.pdf') }}",
    }
  };
  window.sigDocumentosConfig = sigDocumentosConfig;

  const capitalizarFormatter = (value) => {
    if (!value) return '';
    const texto = String(value).toLowerCase();
    return texto.charAt(0).toUpperCase() + texto.slice(1);
  };

  const fechaFormatter = (value) => {
    if (!value) return '';
    const date = new Date(value);
    if (isNaN(date.getTime())) return value;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  window.capitalizarFormatter = capitalizarFormatter;
  window.fechaFormatter = fechaFormatter;

  window.accionesDocumentos = (value, row) => {
    const cfg = window.sigDocumentosConfig || {};
    const rutas = cfg.rutas || {};
    const urlEmisiones = (rutas.emisiones || '#').replace(':id', row.id);
    const urlNueva = (rutas.nueva || '#').replace(':id', row.id);
    const urlEditar = (rutas.editar || '#').replace(':id', row.id);
    const esActivo = row.estado === 'ACTIVO';
    const tieneRevision = !!row.en_revision;

    const btnNueva = (cfg.puedeCrearEmision && !tieneRevision)
      ? `<button
          type="button"
          class="btn btn-link p-0 sig-action-btn"
          data-title="Nueva emisi\u00f3n"
          aria-label="Nueva emisi\u00f3n"
          onclick="cargarModal('${urlNueva}', 'Nueva emisi\u00f3n', '#formNuevaEmision', 'modal-lg')">
          <img src="{{ asset('img/nuevaEmision.png') }}" alt="Nueva emisi\u00f3n" style="width: 32px; height: 32px;">
        </button>`
      : (tieneRevision
        ? `<button type="button" class="btn btn-link p-0 text-muted sig-action-btn" title="Ya existe una emisi\u00f3n en revisi\u00f3n" data-title="Emisi\u00f3n en revisi\u00f3n" aria-label="Emisi\u00f3n en revisi\u00f3n" onclick="avisarEmisionRevision()">
            <img src="{{ asset('img/nuevaEmision.png') }}" alt="Nueva emisi\u00f3n" style="width: 32px; height: 32px;">
          </button>`
        : '');

    const btnEditar = (cfg.puedeEditarDocumento && esActivo)
      ? `<button
          type="button"
          class="btn btn-link p-0 sig-action-btn"
          data-title="Editar"
          aria-label="Editar documento"
          onclick="cargarModal('${urlEditar}', 'Editar documento', '#formEditarDocumento', 'modal-lg')">
          <img src="{{ asset('img/edit.png') }}" alt="Editar" style="width: 32px; height: 32px;">
        </button>`
      : '';

    const pdfUrl = rutas.pdf || '#';
    const tituloPdfBase = row.codigo ? `PDF ${row.codigo}` : 'Documento PDF';
    const tituloPdf = tituloPdfBase.replace(/'/g, "\\'");
    const btnPdf = `
      <button type="button"
        class="btn btn-link p-0 sig-action-btn"
        data-title="PDF" aria-label="Ver PDF"
        onclick="abrirPdfModal('${pdfUrl}', '${tituloPdf}')">
        <img src="{{ asset('img/descargarPDF.png') }}" alt="PDF" style="width: 32px; height: 32px;">
      </button>`;

    return `
      <div class="d-flex justify-content-center gap-2">
        ${btnPdf}
        ${cfg.puedeVerEmisiones ? `
        <button
          type="button"
          class="btn btn-link p-0 sig-action-btn"
          data-title="Historial"
          aria-label="Historial de emisiones"
          onclick="cargarModal('${urlEmisiones}', 'Emisiones del documento', null, 'modal-lg', 'GET')">
          <img src="{{ asset('img/historico.png') }}" alt="Historial" style="width: 32px; height: 32px;">
        </button>` : ''}
        ${btnEditar}
        ${btnNueva}
      </div>
    `;
  };

  window.estadoFormatter = (value, row) => {
    const cfg = window.sigDocumentosConfig || {};
    const rutas = cfg.rutas || {};
    const checked = row.estado === 'ACTIVO' ? 'checked' : '';
    const estadoNuevo = row.estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';
    const urlEstado = (rutas.estado || '#').replace(':id', row.id);
    const disabled = cfg.puedeCambiarEstado ? '' : 'disabled';

    return `
      <div class="form-check form-switch d-flex justify-content-center">
        <input
          onchange="actualizarEstadoDocumento(this, '${urlEstado}', '${estadoNuevo}', ${row.id})"
          class="form-check-input estado-switch"
          type="checkbox"
          role="switch"
          ${checked}
          ${disabled}>
      </div>
    `;
  };

  document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.sigDocumentosConfig || {};
    const datosOriginales = cfg.datosOriginales || [];
    const puedeVerInactivos = cfg.puedeVerInactivos || false;

    initTablaBootstrapTable(
      '#tablaDocumentos',
      { protegidas: ['codigo', 'nombre'], ocultas: ['acciones'], forzarDetalle: true },
      'detalleDocumentos',
      {
        acciones: window.accionesDocumentos,
        nombre: capitalizarFormatter,
        elaboro: capitalizarFormatter,
        reviso: capitalizarFormatter,
        aprueba: capitalizarFormatter,
        ubicacion: capitalizarFormatter,
        fecha_aprobacion: fechaFormatter,
        estado: window.estadoFormatter
      }
    );

    const $tabla = $('#tablaDocumentos');
    const $filtroProceso = $('#filtroProceso');
    const $filtroTipo = $('#filtroTipo');
    const $filtroEstado = $('#filtroEstado');

    const aplicarFiltros = () => {
      const procesoSeleccionado = $filtroProceso.val();
      const tipoSeleccionado = $filtroTipo.val();
      const estadoSeleccionado = $filtroEstado.val();

      const filtrados = datosOriginales.filter(item => {
        const coincideProceso = procesoSeleccionado ? String(item.proceso_id) === procesoSeleccionado : true;
        const coincideTipo = tipoSeleccionado ? String(item.tipo_id) === tipoSeleccionado : true;
        const coincideEstado = estadoSeleccionado === 'todos'
          ? true
          : (estadoSeleccionado === 'activos' ? item.estado === 'ACTIVO' : item.estado === 'INACTIVO');
        return coincideProceso && coincideTipo && coincideEstado;
      });

      $tabla.bootstrapTable('load', filtrados);
    };

    $filtroProceso.on('change', aplicarFiltros);
    $filtroTipo.on('change', aplicarFiltros);
    $filtroEstado.on('change', () => {
      const estado = $filtroEstado.val();
      if (!puedeVerInactivos && estado !== 'activos') {
        $filtroEstado.val('activos');
        return;
      }
      const url = new URL(window.location.href);
      url.searchParams.set('estado', estado);
      window.location.href = url.toString();
    });

    window.actualizarEstadoDocumento = (el, url, estado, id) => {
      const confirmarCambio = () => {
        if (!window.Swal) {
          return Promise.resolve(window.confirm(`Cambiar estado a ${estado}?`));
        }

        return Swal.fire({
          title: `Cambiar estado a ${estado}?`,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'S\u00ed, continuar',
          cancelButtonText: 'Cancelar',
          confirmButtonColor: '#198754',
          cancelButtonColor: '#d33',
          customClass: { popup: 'swalAlert' }
        }).then((result) => result.isConfirmed);
      };

      confirmarCambio().then((confirmado) => {
        if (!confirmado) {
          if (el) el.checked = !el.checked;
          return;
        }

        fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({ estado })
        })
          .then(r => r.ok ? r.json() : Promise.reject(r))
          .then((response) => {
            const doc = datosOriginales.find(d => String(d.id) === String(id));
            if (doc) doc.estado = estado;
            aplicarFiltros();

            if (window.mostrarToast) {
              mostrarToast(response?.message || 'Estado actualizado', 'success');
            }
          })
          .catch(() => {
            if (el) el.checked = !el.checked;
            if (window.mostrarToast) {
              mostrarToast('No se pudo actualizar el estado.', 'danger');
            } else if (window.Swal) {
              Swal.fire({
                title: 'Error',
                text: 'No se pudo actualizar el estado.',
                icon: 'error',
                customClass: { popup: 'swalAlert' }
              });
            }
          });
      });
    };

    window.avisarEmisionRevision = () => {
      const msg = 'No es posible crear una nueva emisi\u00f3n porque ya hay una en revisi\u00f3n.';
      if (window.Swal) {
        Swal.fire({
          title: 'Acci\u00f3n no permitida',
          text: msg,
          icon: 'info',
          customClass: { popup: 'swalAlert' }
        });
      } else {
        alert(msg);
      }
    };
  });
</script>
@endpushOnce
