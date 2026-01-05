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
          Mis emisiones
        </a>
      @endpermite
      @permite(\App\Constants\Permisos::SIG_MAPA_PROCESOS_ACCEDER)
      <a href="{{ route('mapa-procesos.emisiones.pendientes') }}" class="btn btn-warning btn-sm">
        Emisiones en revision
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
      data-puede-inactivos="{{ Auth::user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_ELIMINAR) ? '1' : '0' }}">
      <thead class="table-primary">
        <tr>
          <th data-field="codigo" data-sortable="true" class="text-nowrap">Codigo</th>
          <th data-field="nombre" data-sortable="true" data-formatter="capitalizarFormatter">Nombre</th>
          <th data-field="version" data-sortable="true" data-align="center" class="text-nowrap">Emision</th>
          <th data-field="fecha_aprobacion" data-sortable="true" data-formatter="fechaFormatter" class="text-nowrap">Fecha aprobacion</th>
          <th data-field="elaboro" data-sortable="true" data-formatter="capitalizarFormatter">Elaboro</th>
          <th data-field="reviso" data-sortable="true" data-formatter="capitalizarFormatter">Reviso</th>
          <th data-field="aprueba" data-sortable="true" data-formatter="capitalizarFormatter">Aprueba</th>
          <th data-field="ubicacion" data-sortable="true" data-formatter="capitalizarFormatter">Ubicacion</th>
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
  // Asegura que los formatters existan en el scope global antes de que bootstrap-table inicialice
  window.capitalizarFormatter = window.capitalizarFormatter || function (value) {
    if (!value) return '';
    const texto = String(value).toLowerCase();
    return texto.charAt(0).toUpperCase() + texto.slice(1);
  };
  window.fechaFormatter = window.fechaFormatter || function (value) {
    if (!value) return '';
    const date = new Date(value);
    if (isNaN(date.getTime())) return value;
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
  };

  document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tablaDocumentos');
    const datosOriginales = tabla ? JSON.parse(tabla.getAttribute('data-data') || '[]') : [];
    const puedeCrearEmision = tabla?.dataset.puedeCrear === '1';
    const puedeCambiarEstado = tabla?.dataset.puedeEstado === '1';
    const puedeVerEmisiones = tabla?.dataset.puedeVer === '1';
    const puedeVerInactivos = tabla?.dataset.puedeInactivos === '1';

    const capitalizarFormatter = (value) => {
      if (!value) return '';
      const texto = String(value).toLowerCase();
      return texto.charAt(0).toUpperCase() + texto.slice(1);
    };
    window.capitalizarFormatter = capitalizarFormatter;

    const fechaFormatter = (value) => {
      if (!value) return '';
      const date = new Date(value);
      if (isNaN(date.getTime())) return value;
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };
    window.fechaFormatter = fechaFormatter;

    const accionesDocumentos = (value, row) => {
      const urlEmisiones = "{{ route('mapa-procesos.documento.emisiones', ['id' => ':id']) }}".replace(':id', row.id);
      const urlEstado = "{{ route('mapa-procesos.documento.estado', ['id' => ':id']) }}".replace(':id', row.id);
      const urlNueva = "{{ route('mapa-procesos.documento.emision.nueva', ['id' => ':id']) }}".replace(':id', row.id);
      const esActivo = row.estado === 'ACTIVO';
      const estadoNuevo = esActivo ? 'INACTIVO' : 'ACTIVO';
      const iconToggle = esActivo ? 'fa-trash-alt text-danger' : 'fa-undo text-success';
      const tituloToggle = esActivo ? 'Inactivar' : 'Activar';
      const tieneRevision = !!row.en_revision;

      const btnNueva = (puedeCrearEmision && !tieneRevision)
        ? `<button
            type="button"
            class="btn btn-link p-0 text-success"
            title="Nueva emision"
            onclick="cargarModal('${urlNueva}', 'Nueva emision', '#formNuevaEmision', 'modal-lg')">
            <i class="fas fa-plus-circle fa-lg"></i>
          </button>`
        : (tieneRevision
          ? `<button type="button" class="btn btn-link p-0 text-muted" title="Ya existe una emision en revision" onclick="avisarEmisionRevision()">
              <i class="fas fa-plus-circle fa-lg"></i>
            </button>`
          : '');

      const btnEstado = puedeCambiarEstado
        ? `<button
            type="button"
            class="btn btn-link p-0"
            title="${tituloToggle}"
            onclick="toggleEstado('${urlEstado}', '${estadoNuevo}', ${row.id})">
            <i class="fas ${iconToggle} fa-lg"></i>
          </button>`
        : '';

      const btnPdf = `
        <button type="button" class="btn btn-link p-0" title="Ver PDF (proximamente)" disabled>
          <img src="{{ asset('img/verPDF.png') }}" alt="PDF" style="width: 24px; height: 24px;">
        </button>`;

      return `
        <div class="d-flex justify-content-center gap-2">
          ${btnPdf}
          ${puedeVerEmisiones ? `
          <button
            type="button"
            class="btn btn-link p-0 text-primary"
            title="Historial de emisiones"
            onclick="cargarModal('${urlEmisiones}', 'Emisiones del documento', null, 'modal-lg', 'GET')">
            <i class="fas fa-history fa-lg"></i>
          </button>` : ''}
          ${btnNueva}
          ${btnEstado}
        </div>
      `;
    };
    window.accionesDocumentos = accionesDocumentos;

    initTablaBootstrapTable(
      '#tablaDocumentos',
      { protegidas: ['codigo', 'nombre'] },
      'detalleDocumentos',
      {
        acciones: accionesDocumentos,
        nombre: capitalizarFormatter,
        elaboro: capitalizarFormatter,
        reviso: capitalizarFormatter,
        aprueba: capitalizarFormatter,
        ubicacion: capitalizarFormatter,
        fecha_aprobacion: fechaFormatter
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
        // No permite cambiar a inactivos/todos
        $filtroEstado.val('activos');
        return;
      }
      const url = new URL(window.location.href);
      url.searchParams.set('estado', estado);
      window.location.href = url.toString();
    });

    window.capitalizarFormatter = capitalizarFormatter;
    window.fechaFormatter = fechaFormatter;
    window.accionesDocumentos = accionesDocumentos;

    window.toggleEstado = (url, estado, id) => {
      Swal.fire({
        title: `¿Cambiar a estado ${estado}?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#d33',
        customClass: { popup: 'swalAlert' }
      }).then((result) => {
        if (!result.isConfirmed) return;

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
          .then(() => {
            const doc = datosOriginales.find(d => String(d.id) === String(id));
            if (doc) doc.estado = estado;
            aplicarFiltros();

            Swal.fire({
              title: 'Actualizado',
              text: `El estado se cambió a ${estado}.`,
              icon: 'success',
              timer: 1500,
              showConfirmButton: false,
              customClass: { popup: 'swalAlert' }
            });
          })
          .catch(() => {
            Swal.fire({
              title: 'Error',
              text: 'No se pudo actualizar el estado.',
              icon: 'error',
              customClass: { popup: 'swalAlert' }
            });
          });
      });
    };

    window.avisarEmisionRevision = () => {
      const msg = 'No es posible crear una nueva emisión porque ya hay una en revisión.';
      if (window.Swal) {
        Swal.fire({
          title: 'Acción no permitida',
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
