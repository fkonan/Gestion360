<div class="container-fluid p-3">
  <h6 class="mb-3">{{ $emisionDevuelta ? "Reenviar emisi\u{00f3}n" : "Nueva emisi\u{00f3}n" }}</h6>

  @php
    $seleccionElabora = $emisionDevuelta?->id_elabora ?? $ultimaAprobada?->id_elabora;
  @endphp

  <div class="card shadow-sm">
    <div class="card-header">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
          <div class="fw-bold fs-6">{{ $documento->codigo }}</div>
          <div class="text-muted">{{ $documento->nombre }}</div>
        </div>
        <span class="text-muted small">La emisi&oacute;n se crear&aacute; en estado EN_REVISI&Oacute;N.</span>
      </div>
    </div>

    <div class="card-body">
      <form id="formNuevaEmision" method="POST" action="{{ route('mapa-procesos.documento.emision.guardar', ['id' => $documento->id]) }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
          <div class="col-12">
            <div class="alert alert-info py-2 mb-1">
              Si se aprueba, se asignar&aacute; la versi&oacute;n <strong>{{ $proximaVersion }}</strong>.
            </div>
          </div>
          @if($emisionDevuelta?->comentario_revision)
            <div class="col-12">
              <label class="form-label">Observaci&oacute;n anterior</label>
              <textarea class="form-control" rows="3" readonly>{{ $emisionDevuelta->comentario_revision }}</textarea>
            </div>
          @endif
          <div class="col-12 col-md-4">
            <label class="form-label">Categor&iacute;a</label>
            <input type="text" class="form-control" readonly value="{{ $documento->proceso?->categoria === 'G' ? 'Procesos Gerenciales' : ($documento->proceso?->categoria === 'M' ? 'Procesos Misionales' : ($documento->proceso?->categoria === 'A' ? 'Procesos de Apoyo' : 'Otros Procesos')) }}">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Proceso</label>
            <input type="text" class="form-control" readonly value="{{ $documento->proceso?->nombre }}">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Tipo de documento</label>
            <input type="text" class="form-control" readonly value="{{ $documento->tipoDocumento?->nombre }}">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">C&oacute;digo</label>
            <input type="text" class="form-control" readonly value="{{ $documento->codigo }}">
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label">Nombre</label>
            <input type="text" class="form-control" readonly value="{{ $documento->nombre }}">
          </div>
          <div class="col-12">
            <label class="form-label">Descripci&oacute;n</label>
            <textarea class="form-control" rows="2" readonly>{{ $documento->descripcion }}</textarea>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Ubicaci&oacute;n</label>
            <input type="text" class="form-control" readonly value="{{ $documento->ubicacion?->nombre }}">
          </div>
          <div class="col-12 col-md-4">
            <label for="paginas" class="form-label">P&aacute;ginas</label>
            <input type="number" name="paginas" id="paginas" class="form-control" min="1" value="{{ $emisionDevuelta?->paginas }}">
            <span class="error text-danger fw-bold" id="error-paginas"></span>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Fecha elaboraci&oacute;n</label>
            <input type="text" class="form-control" readonly value="{{ now()->toDateString() }}">
          </div>
          <div class="col-12 col-md-4">
            <label for="id_elabora" class="form-label">Elabora</label>
            <select name="id_elabora" id="id_elabora" class="form-select">
              <option value="">Seleccione</option>
              @foreach($ubicacionesElabora as $ubic)
                <option value="{{ $ubic->id }}" @selected($seleccionElabora == $ubic->id)>
                  {{ $ubic->nombre }}
                </option>
              @endforeach
            </select>
            <span class="error text-danger fw-bold" id="error-id_elabora"></span>
          </div>
          <div class="col-12">
            <label for="comentario_revision" class="form-label">Comentario de la solicitud</label>
            <textarea name="comentario_revision" id="comentario_revision" class="form-control" rows="5" placeholder="Describe brevemente el motivo o los ajustes solicitados"></textarea>
            <span class="error text-danger fw-bold" id="error-comentario_revision"></span>
          </div>
          <div class="col-12 col-md-8">
            <label for="archivo" class="form-label">Archivo (PDF o Word)</label>
            <input type="file" name="archivo" id="archivo" class="form-control" accept=".pdf,.doc,.docx" required>
            @if($emisionDevuelta?->archivo_url)
              <small class="text-muted">Archivo anterior: {{ $emisionDevuelta->archivo_url }}</small>
            @endif
            <span class="error text-danger fw-bold" id="error-archivo"></span>
          </div>
        </div>

        <div class="mt-4 d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Guardar emisi&oacute;n</button>
        </div>
      </form>
    </div>
  </div>
</div>
