<!-- Modal documentos adjuntos -->
<div class="container-fluid">
    <table
        id="adjuntosDataTable"
        class="table table-sm table-striped"
        data-page-size="10"
        data-toggle="table"
        data-locale="es-ES"
        data-search="true"
        data-pagination="true"
        data-pagination-parts="['pageSize', 'pageList', 'pageNext', 'pagePrev']">   
        <thead>
            <tr class="bg-primary">
                <th data-sortable="true">Radicado</th>
                <th data-sortable="true">Tipo </th>
                <th data-sortable="true">Fecha </th>
                <th class="text-center">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($incapacidadDocumentos as $documento)
                <tr>
                    <td>{{ $documento->Radicado ?? 1 }}</td>
                    <td>{{ $documento->tipoDocumento->ParNom }}</td>
                    <td>{{ $documento->DocFecReg }}</td>
                    <td class="text-center">
                        <a href="{{ $documento->Ruta }}" target="_blank">
                            <i class="fas fa-file-pdf fa-2x text-danger"></i>
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
</div>