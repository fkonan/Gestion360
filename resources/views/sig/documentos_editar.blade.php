<div class="container-fluid p-3">
  <h6 class="mb-3">Editar documento</h6>

  <div class="card shadow-sm">
    <div class="card-header">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
          <div class="fw-bold fs-6">{{ $documento->codigo }}</div>
          <div class="text-muted">{{ $documento->nombre }}</div>
        </div>
        <span class="text-muted small">Solo se permite editar nombre y descripci&oacute;n.</span>
      </div>
    </div>

    <div class="card-body">
      <form id="formEditarDocumento" method="POST" action="{{ route('mapa-procesos.documento.actualizar', ['id' => $documento->id]) }}">
        @csrf
        <div class="row g-4">
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
            <label class="form-label">Ubicaci&oacute;n</label>
            <input type="text" class="form-control" readonly value="{{ $documento->ubicacion?->nombre }}">
          </div>
          <div class="col-12 col-md-8">
            <label for="centros_costos" class="form-label">&Aacute;reas que pueden visualizar (opcional)</label>
            <select name="centros_costos[]" id="centros_costos" class="form-select select2" multiple>
              @foreach($centrosCostos as $centro)
                <option value="{{ $centro->codigo }}" @selected(in_array($centro->codigo, $centrosSeleccionados ?? [], true))>
                  {{ $centro->codigo }} - {{ $centro->descripcion }}
                </option>
              @endforeach
            </select>
            <div class="form-text">Si no selecciona ninguno, el documento ser&aacute; visible para todos.</div>
            <span class="error text-danger fw-bold" id="error-centros_costos"></span>
          </div>
          <div class="col-12 col-md-8">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" name="nombre" id="nombre" class="form-control" value="{{ $documento->nombre }}" required>
            <span class="error text-danger fw-bold" id="error-nombre"></span>
          </div>
          <div class="col-12">
            <label for="descripcion" class="form-label">Descripci&oacute;n</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="4" required>{{ $documento->descripcion }}</textarea>
            <span class="error text-danger fw-bold" id="error-descripcion"></span>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">P&aacute;ginas</label>
            <input type="number" class="form-control" readonly value="{{ $ultimaAprobada?->paginas }}">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Fecha elaboraci&oacute;n</label>
            <input type="date" class="form-control" readonly value="{{ $ultimaAprobada?->fecha_elaboracion ? \Illuminate\Support\Carbon::parse($ultimaAprobada->fecha_elaboracion)->format('Y-m-d') : '' }}">
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Elabora</label>
            <select class="form-select" disabled>
              <option value="">Seleccione</option>
              @foreach($ubicacionesElabora as $ubic)
                <option value="{{ $ubic->id }}" @selected($ultimaAprobada?->id_elabora == $ubic->id)>
                  {{ $ubic->nombre }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Revisa</label>
            <select class="form-select" disabled>
              <option value="">Seleccione</option>
              @foreach($ubicacionesRevisa as $ubic)
                <option value="{{ $ubic->id }}" @selected($ultimaAprobada?->id_revisa == $ubic->id)>
                  {{ $ubic->nombre }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Aprueba</label>
            <select class="form-select" disabled>
              <option value="">Seleccione</option>
              @foreach($ubicacionesAprueba as $ubic)
                <option value="{{ $ubic->id }}" @selected($ultimaAprobada?->id_aprueba == $ubic->id)>
                  {{ $ubic->nombre }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Comentario de revisi&oacute;n</label>
            <textarea class="form-control" rows="3" readonly>{{ $ultimaAprobada?->comentario_revision }}</textarea>
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label">Archivo</label>
            <input type="text" class="form-control" readonly value="{{ $ultimaAprobada?->archivo_url }}">
          </div>
        </div>

        <div class="mt-4 d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
