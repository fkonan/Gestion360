@extends('layouts.dashboard')

@section('title', 'Solicitudes pendientes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'SIG', 'url' => route('home')],
        ['name' => 'Solicitudes pendientes']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    titulo="Solicitudes pendientes"
    rutaVolver="javascript:history.back()" />

  <div id="no-more-tables" class="table-responsive" style="padding:1em 1.25em">
    <table
      id="tablaPendientes"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-detail-formatter="detalleSolicitudesPendientes"
      data-pagination="true"
      data-data='@json($versiones)'>
      <thead class="table-primary">
        <tr>
          <th data-field="codigo" data-sortable="true" class="text-nowrap">C&oacute;digo</th>
          <th data-field="nombre" data-sortable="true" data-formatter="capitalizarFormatter">Nombre</th>
          <th data-field="tipo_solicitud" data-sortable="true" data-formatter="tipoSolicitudFormatter" class="text-nowrap">Tipo</th>
          <th data-field="version" data-sortable="true" data-align="center">Emisi&oacute;n</th>
          <th data-field="comentario_revision" data-formatter="observacionFormatter">Comentario</th>
          <th data-field="elaboro" data-formatter="capitalizarFormatter">Elabor&oacute;</th>
          <th data-field="reviso" data-formatter="capitalizarFormatter">Revis&oacute;</th>
          <th data-field="aprueba" data-formatter="capitalizarFormatter">Aprueba</th>
          <th data-field="fecha_elaboracion" data-sortable="true" data-formatter="fechaFormatter">Fecha elaboraci&oacute;n</th>
          <th data-field="estado" data-formatter="estadoFormatter" class="text-center">Estado</th>
          <th data-field="acciones" data-formatter="accionesPendientes" class="text-center">Acciones</th>
        </tr>
      </thead>
    </table>
  </div>
</div>

@endsection

@pushOnce('script')
<script>
  document.addEventListener('DOMContentLoaded', () => {
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

    const tipoSolicitudFormatter = (value) => {
      if (!value) return '';
      const texto = String(value);
      if (texto === 'NUEVO_DOCUMENTO') return 'Nuevo documento';
      return 'Emisi\u00f3n';
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

    const rutaAprobar = "{{ route('mapa-procesos.emisiones.aprobar', ['id' => ':id']) }}";
    const rutaRechazar = "{{ route('mapa-procesos.emisiones.rechazar', ['id' => ':id']) }}";
    const rutaDevolver = "{{ route('mapa-procesos.emisiones.devolver', ['id' => ':id']) }}";

    const estadoFormatter = (value) => {
      const texto = value || 'EN_REVISION';
      let color = 'secondary';
      if (texto === 'EN_REVISION') color = 'warning';
      else if (texto === 'RECHAZADO') color = 'danger';
      else if (texto === 'APROBADO') color = 'success';
      else if (texto === 'DEVUELTO') color = 'info';
      const textoMostrar = texto === 'EN_REVISION' ? 'EN_REVISI\u00d3N' : texto;
      return `<span class="badge bg-${color}">${textoMostrar}</span>`;
    };

    const observacionFormatter = (value) => {
      if (!value) return '';
      const texto = String(value);
      const limite = 80;
      const resumen = texto.length > limite ? `${texto.slice(0, limite)}...` : texto;
      return `<span title="${escapeHtml(texto)}">${escapeHtml(resumen)}</span>`;
    };

    const solicitudesData = JSON.parse(document.getElementById('tablaPendientes')?.getAttribute('data-data') || '[]');

    const mostrarDetalleSolicitud = (id) => {
      const row = solicitudesData.find(item => String(item.id) === String(id));
      if (!row) {
        return;
      }
      const detalles = [
        ['C\u00f3digo', row.codigo],
        ['Nombre', row.nombre],
        ['Tipo', row.tipo_solicitud === 'NUEVO_DOCUMENTO' ? 'Documento nuevo' : 'Emisi\u00f3n'],
        ['Emisi\u00f3n', row.version || 'Sin asignar'],
        ['Elabor\u00f3', row.elaboro],
        ['Revis\u00f3', row.reviso],
        ['Aprueba', row.aprueba],
        ['P\u00e1ginas', row.paginas],
        ['Fecha elaboraci\u00f3n', row.fecha_elaboracion],
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

    const accionesPendientes = (value, row) => {
      const urlA = rutaAprobar.replace(':id', row.id);
      const urlR = rutaRechazar.replace(':id', row.id);

      const urlD = rutaDevolver.replace(':id', row.id);

      return `
        <div class="d-flex justify-content-center gap-1">
          <button type="button"
            data-title="Ver detalle"
            aria-label="Ver detalle"
            class="btn p-0 text-secondary sig-action-btn"
            onclick="mostrarDetalleSolicitud(${row.id})">
            <img src="{{ asset('img/verDetalle.png') }}" alt="Ver detalle" style="width: 26px; height: 26px;">
          </button>
          <button type="button"
            data-title="Aprobar"
            aria-label="Aprobar"
            class="btn p-0 text-success sig-action-btn"
            onclick="actualizarEmision('${urlA}', 'APROBADO', ${row.id}, '${row.tipo_solicitud || ''}')">
            <img src="{{ asset('img/aprobar.png') }}" alt="Aprobar" style="width: 30px; height: 30px;">
          </button>
          <button type="button"
            data-title="Rechazar"
            aria-label="Rechazar"
            class="btn p-0 text-danger sig-action-btn"
            onclick="actualizarEmision('${urlR}', 'RECHAZADO', ${row.id}, '${row.tipo_solicitud || ''}')">
            <img src="{{ asset('img/rechazar.png') }}" alt="Rechazar" style="width: 36px; height: 36px;">
          </button>
          <button type="button"
            data-title="Devolver"
            aria-label="Devolver"
            class="btn p-0 text-warning sig-action-btn"
            onclick="actualizarEmision('${urlD}', 'DEVUELTO', ${row.id}, '${row.tipo_solicitud || ''}')">
            <img src="{{ asset('img/devolver.png') }}" alt="Devolver" style="width: 34px; height: 34px;">
          </button>
        </div>
      `;
    };

    initTablaBootstrapTable(
      '#tablaPendientes',
      { protegidas: ['codigo', 'nombre'], ocultas: ['acciones'], forzarDetalle: true },
      'detalleSolicitudesPendientes',
      {
        acciones: accionesPendientes,
        tipo_solicitud: tipoSolicitudFormatter,
        nombre: capitalizarFormatter,
        comentario_revision: observacionFormatter,
        elaboro: capitalizarFormatter,
        reviso: capitalizarFormatter,
        aprueba: capitalizarFormatter,
        fecha_elaboracion: fechaFormatter,
        estado: estadoFormatter
      }
    );

    window.capitalizarFormatter = capitalizarFormatter;
    window.tipoSolicitudFormatter = tipoSolicitudFormatter;
    window.fechaFormatter = fechaFormatter;
    window.estadoFormatter = estadoFormatter;
    window.accionesPendientes = accionesPendientes;
    window.observacionFormatter = observacionFormatter;
    window.mostrarDetalleSolicitud = mostrarDetalleSolicitud;

    const obtenerEtiquetaSolicitud = (tipo) => {
      return tipo === 'NUEVO_DOCUMENTO' ? 'documento nuevo' : 'emisi\u00f3n';
    };

    window.actualizarEmision = (url, estado, id, tipoSolicitud) => {
      const accion = estado === 'APROBADO' ? 'aprobar' : (estado === 'RECHAZADO' ? 'rechazar' : 'devolver');
      Swal.fire({
        title: `\u00bfDeseas ${accion} esta ${obtenerEtiquetaSolicitud(tipoSolicitud)}?`,
        input: 'textarea',
        inputPlaceholder: 'Escribe un comentario',
        inputAttributes: { 'aria-label': 'Comentario' },
        inputValidator: (value) => {
          if (!value) {
            return 'El comentario es obligatorio';
          }
        },
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Confirmar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#d33',
        customClass: { popup: 'swalAlert' }
      }).then((result) => {
        if (!result.isConfirmed) return;

        const comentario = result.value || '';

        fetch(url, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ comentario })
        })
          .then(r => r.ok ? r.json() : Promise.reject(r))
          .then((resp) => {
            $('#tablaPendientes').bootstrapTable('remove', { field: 'id', values: [id] });
            Swal.fire({
              title: resp.title || 'Actualizado',
              text: resp.message || 'Operaci\u00f3n realizada',
              icon: resp.type === 'success' ? 'success' : 'info',
              timer: 1500,
              showConfirmButton: false,
              customClass: { popup: 'swalAlert' }
            });
          })
          .catch(async (err) => {
            let mensaje = 'No se pudo actualizar la emisi\u00f3n.';
            try {
              const json = await err.json();
              mensaje = json.message || mensaje;
            } catch (_) { }
            Swal.fire({
              title: 'Error',
              text: mensaje,
              icon: 'error',
            });
          });
      });
    };
  });
</script>
@endpushOnce
