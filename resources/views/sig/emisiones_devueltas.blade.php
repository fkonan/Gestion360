@extends('layouts.dashboard')

@section('title', 'Mis emisiones')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'SIG', 'url' => route('home')],
        ['name' => 'Mis emisiones']
    ]" />
<br>
@endsection

@section('content')
<div class="container-fluid p-0 border rounded sidebar-dark-primary tableContainer" style="min-height:150px;">

  <x-sectionHeader
    titulo="Mis emisiones"
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
      data-data='@json($versiones)'>
      <thead class="table-primary">
        <tr>
          <th data-field="codigo" data-sortable="true" class="text-nowrap">Codigo</th>
          <th data-field="nombre" data-sortable="true" data-formatter="capitalizarFormatter">Nombre</th>
          <th data-field="version" data-align="center">Emision</th>
          <th data-field="comentario_revision" data-formatter="capitalizarFormatter">Observacion</th>
          <th data-field="archivo_url" class="text-nowrap">Archivo</th>
          <th data-field="paginas">Paginas</th>
          <th data-field="fecha_elaboracion" data-formatter="fechaFormatter" data-sortable="true" class="text-nowrap">Fecha</th>
          <th data-field="estado" data-formatter="estadoFormatter" class="text-center">Estado</th>
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

    const estadoFormatter = (value) => {
      const texto = value || 'DEVUELTO';
      const color = texto === 'DEVUELTO' ? 'info' : (texto === 'RECHAZADO' ? 'danger' : (texto === 'APROBADO' ? 'success' : 'secondary'));
      return `<span class="badge bg-${color}">${texto}</span>`;
    };

    initTablaBootstrapTable(
      '#tablaDevueltas',
      { protegidas: ['codigo', 'nombre'] },
      null,
      {
        nombre: capitalizarFormatter,
        comentario_revision: capitalizarFormatter,
        estado: estadoFormatter,
        fecha_elaboracion: fechaFormatter
      }
    );

    window.capitalizarFormatter = capitalizarFormatter;
    window.fechaFormatter = fechaFormatter;
    window.estadoFormatter = estadoFormatter;
  });
</script>
@endpushOnce
