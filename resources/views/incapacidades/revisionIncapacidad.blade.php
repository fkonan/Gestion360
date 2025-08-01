<!-- Modal datos incapacidad -->
<div class="container-fluid p-0 rounded">
    <form 
        id="formIncapacidad" 
        action="{{ route('gestion-empleado.incapacidades.update', ['id' => $incapacidad->IdIncapacidad]) }}" 
        method="POST" 
        enctype="multipart/form-data">
        
        @csrf
        @method('PUT')
        <div class="row g-3 p-4">       
            <div class="col-md-2">
                <label for="IdIncapacidad" class="form-label">Radicado</label>
                <input type="text" class="form-control" id="IdIncapacidad" name="IdIncapacidad" value="{{ $incapacidad->IdIncapacidad}}" disabled>
                <span class="error text-danger fw-bold" id="error-IdIncapacidad"></span>
            </div>
            <div class="col-md-6">
                <label for="CausaId" class="form-label">Causa Incapacidad</label>
                <select class="form-select select2" id="CausaId" name="CausaId" disabled>
                    @foreach ($causasIncapacidad as $causa)
                        <option value="{{ $causa->IdParametro }}" @selected($causa->IdParametro == $incapacidad->CausaId)>
                            {{ $causa->ParNom }}
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-CausaId"></span>
            </div>
            <div class="col-md-4">
                <label for="Diagnostico" class="form-label">Código Incapacidad</label>
                <select class="form-select select2" id="Diagnostico" name="Diagnostico" disabled>
                    @foreach ($codigosIncapacidad as $id => $codigo)
                        <option value="{{ $id }}" @selected($id == $incapacidad->Diagnostico)>
                            {{ $codigo }}
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-Diagnostico"></span>
            </div>   
        </div>
        <div class="row px-4 pb-4">       
            <div class="col-md-4">
                <label for="EPSId" class="form-label">EPS</label>
                <select class="form-control select2" id="EPSId" name="EPSId" disabled>
                    @foreach ($listaEps as $id => $nombre)
                        <option value="{{ $id }}" @selected($id == $incapacidad->EPSId)>
                            {{ $nombre }}
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-EPSId"></span>
            </div>
            <div class="col-md-4">
                <label for="ARLId" class="form-label">ARL</label>
                <select class="form-control select2" id="ARLId" name="ARLId" disabled>
                    @foreach ($listaArl as $id => $nombre)
                        <option value="{{ $id }}" @selected($id == $incapacidad->ARLId)>
                            {{ $nombre }}
                        </option>
                    @endforeach
                </select>
                <span class="error text-danger fw-bold" id="error-ARLId"></span>
            </div>
            <div class="col-md-4">
                <label for="IncTipo" class="form-label">Tipo Incapacidad</label>
                <select class="form-control select2" id="IncTipo" name="IncTipo" disabled>
                    <option value="INICIO" @selected($incapacidad->IncTipo == 'INICIO')>INICIO</option>
                    <option value="PRORROGA" @selected($incapacidad->IncTipo == 'PRORROGA')>PRORROGA</option>
                </select>
                <span class="error text-danger fw-bold" id="error-IncTipo"></span>
            </div>
        </div>

        <div class="row px-4 pb-4">       
            <div class="col-md-6">
                <label for="IncFecIni" class="form-label">Fecha Inicio Incapacidad</label>
                <input type="date" class="form-control" id="IncFecIni" name="IncFecIni" value="{{ $incapacidad->IncFecIni }}" disabled>
                <span class="error text-danger fw-bold" id="error-IncFecIni"></span>
            </div>
            <div class="col-md-6">
                <label for="IncFecFin" class="form-label">Fecha Fin Incapacidad</label>
                <input type="date" class="form-control" id="IncFecFin" name="IncFecFin" value="{{ $incapacidad->IncFecFin }}" disabled>
                <span class="error text-danger fw-bold" id="error-IncFecFin"></span>
            </div>
        </div>

        <input type="hidden" name="RevisionDatos" value="1">

        <div class="text-start m-4">
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" onclick="habilitarInputs()" class="btn btn-warning">Modificar</button>
        </div>
    </form>
</div>


