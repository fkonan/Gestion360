<div class="container-fluid p-3">
  <h6 class="mb-3">Nueva emision</h6>

  <div class="card shadow-sm">
    <div class="card-header">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
          <div class="fw-bold fs-6">{{ $documento->codigo }}</div>
          <div class="text-muted">{{ $documento->nombre }}</div>
        </div>
        <span class="text-muted small">La emision se creara en estado EN_REVISION.</span>
      </div>
    </div>

    <div class="card-body">
      <form id="formNuevaEmision" method="POST" action="{{ route('mapa-procesos.documento.emision.guardar', ['id' => $documento->id]) }}">
        @csrf
        <div class="row g-4">
          <div class="col-12">
            <div class="alert alert-info py-2 mb-1">
              Si se aprueba, se asignara la version <strong>{{ $proximaVersion }}</strong>.
            </div>
          </div>
          <div class="col-12 col-md-8">
            <label for="archivo_url" class="form-label">Archivo (URL)</label>
            <input type="text" name="archivo_url" id="archivo_url" class="form-control" required>
            <span class="error text-danger fw-bold" id="error-archivo_url"></span>
          </div>
          <div class="col-12 col-md-4">
            <label for="paginas" class="form-label">Paginas</label>
            <input type="number" name="paginas" id="paginas" class="form-control" min="1">
            <span class="error text-danger fw-bold" id="error-paginas"></span>
          </div>
          <div class="col-12">
            <label for="comentario_revision" class="form-label">Comentario de revision</label>
            <textarea name="comentario_revision" id="comentario_revision" class="form-control" rows="5" placeholder="Describe brevemente los ajustes o comentarios"></textarea>
            <span class="error text-danger fw-bold" id="error-comentario_revision"></span>
          </div>
          <div class="col-12 col-md-4">
            <label for="id_elabora" class="form-label">Elabora</label>
            <select name="id_elabora" id="id_elabora" class="form-select">
              <option value="">Seleccione</option>
              @foreach($ubicacionesElabora as $ubic)
                <option value="{{ $ubic->id }}">{{ $ubic->nombre }}</option>
              @endforeach
            </select>
            <span class="error text-danger fw-bold" id="error-id_elabora"></span>
          </div>
          <div class="col-12 col-md-4">
            <label for="id_revisa" class="form-label">Revisa</label>
            <select name="id_revisa" id="id_revisa" class="form-select">
              <option value="">Seleccione</option>
              @foreach($ubicacionesRevisa as $ubic)
                <option value="{{ $ubic->id }}">{{ $ubic->nombre }}</option>
              @endforeach
            </select>
            <span class="error text-danger fw-bold" id="error-id_revisa"></span>
          </div>
          <div class="col-12 col-md-4">
            <label for="id_aprueba" class="form-label">Aprueba</label>
            <select name="id_aprueba" id="id_aprueba" class="form-select">
              <option value="">Seleccione</option>
              @foreach($ubicacionesAprueba as $ubic)
                <option value="{{ $ubic->id }}">{{ $ubic->nombre }}</option>
              @endforeach
            </select>
            <span class="error text-danger fw-bold" id="error-id_aprueba"></span>
          </div>
          <div class="col-12 col-md-6">
            <label for="fecha_elaboracion" class="form-label">Fecha elaboracion</label>
            <input type="date" name="fecha_elaboracion" id="fecha_elaboracion" class="form-control" required>
            <span class="error text-danger fw-bold" id="error-fecha_elaboracion"></span>
          </div>
        </div>

        <div class="mt-4 d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Guardar emision</button>
        </div>
      </form>
    </div>
  </div>
</div>
