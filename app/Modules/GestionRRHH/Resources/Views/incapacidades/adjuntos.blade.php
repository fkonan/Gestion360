<!-- Modal documentos adjuntos -->
<div class="table-responsive">
  <table
    id="adjuntosDataTable"
    class="table table-hover align-middle"
    data-toggle="table"
    data-locale="es-ES"
    data-search="true"
    data-pagination="true"
    data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">
    <thead>
      <tr>
        <th data-sortable="true">Radicado</th>
        <th data-sortable="true">Tipo</th>
        <th data-sortable="true">Fecha</th>
        <th class="text-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      @foreach($incapacidadDocumentos as $documento)
      <tr class="align-middle">
        <td class="align-middle">{{ $documento->IncapacidadId ?? 1 }}</td>
        <td class="align-middle">{{ $documento->tipoDocumento->ParNom }}</td>
        <td class="align-middle">{{ $documento->DocFecReg }}</td>
        <td class="text-center align-middle">
          <button type="button" class="btn btn-link m-0 p-0" onclick="abrirArchivo('{{ route('gestion-empleado.incapacidades.documentos.ver', ['documento' => $documento->IdDocumento]) }}')">
            <img src="{{ asset('img/verPDF.png') }}" alt="Adjunto" style="width: 38px; height: 38px;">
          </button>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
