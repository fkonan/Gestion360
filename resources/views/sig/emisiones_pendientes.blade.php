@extends('layouts.dashboard')

@section('title', 'Emisiones pendientes')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'SIG', 'url' => route('home')],
        ['name' => 'Emisiones pendientes']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    titulo="Emisiones pendientes"
    rutaVolver="javascript:history.back()" />

  <div id="no-more-tables" class="table-responsive" style="padding:1em 1.25em">
    <table
      id="tablaPendientes"
      class="table table-sm table-striped"
      data-page-size="25"
      data-toggle="table"
      data-locale="es-ES"
      data-search="true"
      data-pagination="true"
      data-data='@json($versiones)'>
      <thead class="table-primary">
        <tr>
          <th data-field="codigo" data-sortable="true" class="text-nowrap">Codigo</th>
          <th data-field="nombre" data-sortable="true" data-formatter="capitalizarFormatter">Nombre</th>
          <th data-field="version" data-sortable="true" data-align="center">Emision</th>
          <th data-field="comentario_revision" data-formatter="capitalizarFormatter">Comentario</th>
          <th data-field="elaboro" data-formatter="capitalizarFormatter">Elaboro</th>
          <th data-field="reviso" data-formatter="capitalizarFormatter">Reviso</th>
          <th data-field="aprueba" data-formatter="capitalizarFormatter">Aprueba</th>
          <th data-field="fecha_elaboracion" data-sortable="true" data-formatter="fechaFormatter">Fecha elaboracion</th>
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
      return `<span class="badge bg-${color}">${texto}</span>`;
    };

    const accionesPendientes = (value, row) => {
      const urlA = rutaAprobar.replace(':id', row.id);
      const urlR = rutaRechazar.replace(':id', row.id);

      const urlD = rutaDevolver.replace(':id', row.id);

      return `
        <div class="d-flex justify-content-center gap-2">
          <button type="button" class="btn btn-link p-0 text-success" title="Aprobar" onclick="actualizarEmision('${urlA}', 'APROBADO', ${row.id})">
            <i class="fas fa-check-circle fa-lg"></i>
          </button>
          <button type="button" class="btn btn-link p-0 text-danger" title="Rechazar" onclick="actualizarEmision('${urlR}', 'RECHAZADO', ${row.id})">
            <i class="fas fa-times-circle fa-lg"></i>
          </button>
          <button type="button" class="btn btn-link p-0 text-warning" title="Devolver" onclick="actualizarEmision('${urlD}', 'DEVUELTO', ${row.id})">
            <i class="fas fa-undo-alt fa-lg"></i>
          </button>
        </div>
      `;
    };

    initTablaBootstrapTable(
      '#tablaPendientes',
      { protegidas: ['codigo', 'nombre'] },
      null,
      {
        acciones: accionesPendientes,
        nombre: capitalizarFormatter,
        comentario_revision: capitalizarFormatter,
        elaboro: capitalizarFormatter,
        reviso: capitalizarFormatter,
        aprueba: capitalizarFormatter,
        fecha_elaboracion: fechaFormatter,
        estado: estadoFormatter
      }
    );

    window.capitalizarFormatter = capitalizarFormatter;
    window.fechaFormatter = fechaFormatter;
    window.estadoFormatter = estadoFormatter;
    window.accionesPendientes = accionesPendientes;

    window.actualizarEmision = (url, estado, id) => {
      const accion = estado === 'APROBADO' ? 'aprobar' : (estado === 'RECHAZADO' ? 'rechazar' : 'devolver');
      Swal.fire({
        title: `¿Deseas ${accion} esta emision?`,
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
              text: resp.message || 'Operacion realizada',
              icon: resp.type === 'success' ? 'success' : 'info',
              timer: 1500,
              showConfirmButton: false,
            });
          })
          .catch(async (err) => {
            let mensaje = 'No se pudo actualizar la emision.';
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
