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
                <td class="align-middle">{{ $documento->Radicado ?? 1 }}</td>
                <td class="align-middle">{{ $documento->tipoDocumento->ParNom }}</td>
                <td class="align-middle">{{ $documento->DocFecReg }}</td>
                <td class="text-center align-middle">
                <button type="button" class="btn btn-link m-0 p-0" onclick="abrirArchivo('{{ $documento->Ruta }}')">
                    <img class="m-0 p-0" src="https://autogestion.copetran.com.co/gestion_2/aFrame/library/bower_components/Ionicons/png/512/pdf.png" alt="Adjunto" style="width: 35px; height: 35px;">
                </button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>