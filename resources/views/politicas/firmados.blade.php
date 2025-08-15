<!-- Resultado busqueda  -->
<hr class="p-1">

<div class="container-fluid my-3 p-1 rounded">
    <div class="row me-2">
        <div class="alert alert-info p-3 mx-2 small">
            <div class="d-flex flex-wrap align-items-center gap-1 gap-md-3">
                <span><strong>Nombre completo:</strong> {{ $firmas[0]->NomCon }}</span>
                <span><strong>Documento:</strong> {{ $firmas[0]->DocCon }}</span>
                <span><strong>Código:</strong> {{ $firmas[0]->CodCon }}</span>
            </div>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-12">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Política</th>
                            <th scope="col">Fecha de Registro</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($firmas as $firma)
                        <tr>
                            <td>{{ $firma->politica }}</td>
                            <td>{{ $firma->FirFecReg }}   {{ $firma->FirHorReg }}</td>
                            <td class="text-center">
                                <form action="{{ route('firmas.descargar') }}" method="POST" class="d-inline">
                                    @csrf
                                    <input type="hidden" name="firma_id" value="{{ $firma->IdFirma }}">
                                    <button type="submit" class="btn btn-link m-0 p-0">
                                        <img src="{{ asset('img/descargarPDF.png') }}" 
                                            alt="Descargar" 
                                            title="Descargar PDF"
                                            style="width: 38px; height: 38px;">
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>