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

    const fechaTablaFormatter = (value) => {
      if (!value) return '';
      const date = new Date(value);
      if (isNaN(date.getTime())) return value;
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    };

    const fechaFormatter = (value) => {
      if (!value) return '';
      const date = new Date(value);
      if (isNaN(date.getTime())) return value;
      return new Intl.DateTimeFormat('es-CO', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
      }).format(date);
    };

    const obtenerTipoSolicitud = (row) => {
      return row?.tipo_solicitud === 'NUEVO_DOCUMENTO' ? 'Documento nuevo' : 'Emisi\u00f3n';
    };

    const obtenerEtiquetaEstado = (value) => {
      const texto = String(value || 'DEVUELTO');
      return texto === 'EN_REVISION' ? 'EN REVISI\u00d3N' : texto.replaceAll('_', ' ');
    };

    const obtenerClaseEstado = (value) => {
      const texto = String(value || 'DEVUELTO');
      if (texto === 'DEVUELTO') return 'info';
      if (texto === 'RECHAZADO') return 'danger';
      if (texto === 'APROBADO') return 'success';
      if (texto === 'EN_REVISION') return 'warning';
      return 'secondary';
    };

    const estadoFormatter = (value) => {
      const color = obtenerClaseEstado(value);
      return `<span class="badge bg-${color}">${escapeHtml(obtenerEtiquetaEstado(value))}</span>`;
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

      const renderDetailCard = (label, value, extraClass = '') => {
        if (value === null || value === undefined || String(value).trim() === '') {
          return '';
        }

        return `
          <article class="sig-detail-card ${extraClass}">
            <span class="sig-detail-card__label">${escapeHtml(label)}</span>
            <strong class="sig-detail-card__value">${escapeHtml(String(value))}</strong>
          </article>
        `;
      };

      const codigo = row.codigo || 'Sin c\u00f3digo asignado';
      const nombre = row.nombre || 'Solicitud sin nombre';
      const tipoSolicitud = obtenerTipoSolicitud(row);
      const estadoLabel = obtenerEtiquetaEstado(row.estado);
      const estadoClase = obtenerClaseEstado(row.estado);
      const version = row.version ? `Emisi\u00f3n ${row.version}` : 'Sin emisi\u00f3n asignada';
      const observacion = row.comentario_revision
        ? escapeHtml(String(row.comentario_revision))
        : 'Sin observaci\u00f3n registrada.';

      const informacionHtml = [
        renderDetailCard('Tipo de solicitud', tipoSolicitud),
        renderDetailCard('Estado actual', estadoLabel),
        renderDetailCard('Emisi\u00f3n', version),
        renderDetailCard('Archivo', row.archivo_url || 'Sin archivo asociado'),
        renderDetailCard('P\u00e1ginas', row.paginas || 'No registradas'),
        renderDetailCard('C\u00f3digo', codigo),
      ].join('');

      const fechasHtml = [
        renderDetailCard('Fecha de elaboraci\u00f3n', fechaFormatter(row.fecha_elaboracion) || 'Sin fecha'),
        renderDetailCard('Fecha de revisi\u00f3n', fechaFormatter(row.fecha_revision) || 'Pendiente'),
        renderDetailCard('Fecha de aprobaci\u00f3n', fechaFormatter(row.fecha_aprobacion) || 'Pendiente'),
      ].join('');

      const responsablesHtml = [
        renderDetailCard('Elabor\u00f3', row.elaboro || 'No asignado', 'sig-detail-card--person'),
        renderDetailCard('Revis\u00f3', row.reviso || 'No asignado', 'sig-detail-card--person'),
        renderDetailCard('Aprueba', row.aprueba || 'No asignado', 'sig-detail-card--person'),
      ].join('');

      const alertaHtml = row.estado === 'DEVUELTO' && puedeReenviar
        ? `
          <div class="sig-detail-alert sig-detail-alert--info">
            Puedes reenviar esta solicitud desde el bot\u00f3n de reenviar en la tabla.
          </div>
        `
        : '';

      const html = `
        <div class="sig-detail-modal text-start">
          <section class="sig-detail-header">
            <div class="sig-detail-header__top">
              <div>
                <span class="sig-detail-header__eyebrow">Solicitud SIG</span>
                <h5 class="sig-detail-title mb-1">${escapeHtml(nombre)}</h5>
                <p class="sig-detail-subtitle mb-0">${escapeHtml(codigo)}</p>
              </div>
              <span class="sig-detail-status sig-detail-status--${estadoClase}">${escapeHtml(estadoLabel)}</span>
            </div>
            <div class="sig-detail-header__meta">
              <span><strong>Solicitud:</strong> #${escapeHtml(String(row.id))}</span>
              <span><strong>Documento:</strong> ${escapeHtml(String(row.documento_id || 'N/D'))}</span>
            </div>
          </section>

          ${alertaHtml}

          <section class="sig-detail-block">
            <div class="sig-detail-block__header">
              <h6 class="sig-detail-block__title">Informaci\u00f3n general</h6>
            </div>
            <div class="sig-detail-facts sig-detail-facts--general">
              ${informacionHtml}
            </div>
          </section>

          <section class="sig-detail-block">
            <div class="sig-detail-block__header">
              <h6 class="sig-detail-block__title">Fechas del proceso</h6>
            </div>
            <div class="sig-detail-facts sig-detail-facts--dates">
              ${fechasHtml}
            </div>
          </section>

          <section class="sig-detail-block">
            <div class="sig-detail-block__header">
              <h6 class="sig-detail-block__title">Responsables</h6>
            </div>
            <div class="sig-detail-people">
              ${responsablesHtml}
            </div>
          </section>

          <section class="sig-detail-block sig-detail-block--wide">
            <div class="sig-detail-block__header">
              <h6 class="sig-detail-block__title">Observaci\u00f3n</h6>
            </div>
            <div class="sig-detail-note">
              ${observacion}
            </div>
          </section>
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
        fecha_elaboracion: fechaTablaFormatter,
        acciones: accionesSolicitudes
      }
    );

    window.capitalizarFormatter = capitalizarFormatter;
    window.fechaFormatter = fechaTablaFormatter;
    window.estadoFormatter = estadoFormatter;
    window.accionesSolicitudes = accionesSolicitudes;
    window.observacionFormatter = observacionFormatter;
    window.mostrarDetalleSolicitud = mostrarDetalleSolicitud;
  });
</script>
@endpushOnce
