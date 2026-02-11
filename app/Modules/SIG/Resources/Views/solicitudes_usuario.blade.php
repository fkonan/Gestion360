@extends('layouts.dashboard')

@section('title', 'Mis solicitudes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'SIG', 'url' => route('home')],
        ['name' => 'Mis solicitudes']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    titulo="Mis solicitudes"
    rutaVolver="javascript:history.back()" />

  <div id="no-more-tables" class="table-responsive" style="padding:1em 1.25em">
    <table
      id="tablaDevueltas"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-pagination="true"
      data-detail-view="true"
      data-detail-formatter="detalleSolicitudesUsuario"
      data-data='@json($versiones)'
      data-puede-reenviar="{{ Auth::user()?->can(\App\Constants\Permisos::SIG_MAPA_PROCESOS_CREAR_EMISION) ? '1' : '0' }}">
      <thead class="table-primary">
        <tr>
          <th data-field="codigo" data-sortable="true" class="text-nowrap">C&oacute;digo</th>
          <th data-field="nombre" data-sortable="true" data-formatter="capitalizarFormatter">Nombre</th>
          <th data-field="version" data-align="center">Emisi&oacute;n</th>
          <th data-field="comentario_revision" data-formatter="observacionFormatter">Observaci&oacute;n</th>
          <th data-field="archivo_url" class="text-nowrap">Archivo</th>
          <th data-field="paginas">P&aacute;ginas</th>
          <th data-field="fecha_elaboracion" data-formatter="fechaFormatter" data-sortable="true" class="text-nowrap">Fecha</th>
          <th data-field="estado" data-formatter="estadoFormatter" class="text-center">Estado</th>
          <th data-field="acciones" data-formatter="accionesSolicitudes" class="text-center">Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const tabla = document.getElementById('tablaDevueltas');
    const puedeReenviar = tabla?.dataset.puedeReenviar === '1';
    const rutaNueva = "{{ route('mapa-procesos.documento.emision.nueva', ['id' => ':id']) }}";

    const escapeHtml = (value) => String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');

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

    const estadoFormatter = (value) => {
      const texto = value || 'DEVUELTO';
      const color = texto === 'DEVUELTO' ? 'info' : (texto === 'RECHAZADO' ? 'danger' : (texto === 'APROBADO' ? 'success' : 'secondary'));
      return `<span class="badge bg-${color}">${texto}</span>`;
    };

    const observacionFormatter = (value) => {
      if (!value) return '';
      const texto = String(value);
      const limite = 80;
      const resumen = texto.length > limite ? `${texto.slice(0, limite)}...` : texto;
      return `<span title="${escapeHtml(texto)}">${escapeHtml(resumen)}</span>`;
    };

    const solicitudesData = JSON.parse(tabla?.getAttribute('data-data') || '[]');

    const mostrarDetalleSolicitud = (id) => {
      const row = solicitudesData.find(item => String(item.id) === String(id));
      if (!row) {
        return;
      }

      const detalles = [
        ['C\u00f3digo', row.codigo],
        ['Nombre', row.nombre],
        ['Emisi\u00f3n', row.version || 'Sin asignar'],
        ['Archivo', row.archivo_url],
        ['P\u00e1ginas', row.paginas],
        ['Fecha', row.fecha_elaboracion],
        ['Estado', row.estado],
      ];

      const detallesHtml = detalles
        .filter(([, valor]) => valor !== null && valor !== undefined && String(valor).trim() !== '')
        .map(([label, valor]) => `
          <div class="d-flex justify-content-between gap-3 py-1 border-bottom">
            <strong class="text-muted">${escapeHtml(label)}:</strong>
            <span class="text-end">${escapeHtml(String(valor))}</span>
          </div>
        `).join('');

      const observacion = row.comentario_revision
        ? escapeHtml(String(row.comentario_revision))
        : 'Sin observaci\u00f3n registrada.';

      const html = `
        <div class="text-start">
          <div class="mb-3">
            <h6 class="mb-2">Observaci\u00f3n</h6>
            <div class="p-3 rounded border" style="white-space: pre-wrap;">
              ${observacion}
            </div>
          </div>
          <div class="border rounded">
            <div class="p-3">
              ${detallesHtml}
            </div>
          </div>
        </div>
      `;

      const modalEl = document.getElementById('globalModal');
      const modalTitle = document.getElementById('globalModalTitle');
      const modalContent = document.getElementById('globalModalContent');
      if (!modalEl || !modalTitle || !modalContent || !window.bootstrap?.Modal) {
        return;
      }

      modalTitle.textContent = 'Detalle de solicitud';
      modalContent.innerHTML = html;

      const dialogEl = modalEl.querySelector('.modal-dialog');
      if (dialogEl) {
        dialogEl.classList.add('modal-dialog-scrollable', 'modal-lg');
        dialogEl.classList.remove('modal-xl');
      }

      const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
      modal.show();

      const resetDialog = () => {
        if (dialogEl) {
          dialogEl.classList.remove('modal-dialog-scrollable', 'modal-lg');
          dialogEl.classList.add('modal-xl');
        }
        modalEl.removeEventListener('hidden.bs.modal', resetDialog);
      };
      modalEl.addEventListener('hidden.bs.modal', resetDialog);
    };

    const accionesSolicitudes = (value, row) => {
      const url = `${rutaNueva.replace(':id', row.documento_id)}?version_id=${row.id}`;
      const botonReenviar = puedeReenviar && row.estado === 'DEVUELTO'
        ? `
          <button type="button"
            class="btn btn-link p-0 sig-action-btn"
            data-title="Reenviar solicitud"
            aria-label="Reenviar solicitud"
            onclick="cargarModal('${url}', 'Reenviar solicitud', '#formNuevaEmision', 'modal-lg')">
            <img src="{{ asset('img/nuevaEmision.png') }}" alt="Reenviar" style="width: 32px; height: 32px;">
          </button>
        `
        : '';

      return `
        <div class="d-flex justify-content-center gap-2">
          <button type="button"
            class="btn btn-link p-0 sig-action-btn"
            data-title="Ver detalle"
            aria-label="Ver detalle"
            onclick="mostrarDetalleSolicitud(${row.id})">
            <img src="{{ asset('img/verDetalle.png') }}" alt="Ver detalle" style="width: 26px; height: 26px;">
          </button>
          ${botonReenviar}
        </div>
      `;
    };

    initTablaBootstrapTable(
      '#tablaDevueltas',
      { protegidas: ['codigo', 'nombre'] },
      'detalleSolicitudesUsuario',
      {
        nombre: capitalizarFormatter,
        comentario_revision: observacionFormatter,
        estado: estadoFormatter,
        fecha_elaboracion: fechaFormatter,
        acciones: accionesSolicitudes
      }
    );

    window.capitalizarFormatter = capitalizarFormatter;
    window.fechaFormatter = fechaFormatter;
    window.estadoFormatter = estadoFormatter;
    window.accionesSolicitudes = accionesSolicitudes;
    window.observacionFormatter = observacionFormatter;
    window.mostrarDetalleSolicitud = mostrarDetalleSolicitud;
  });
</script>
@endpushOnce
