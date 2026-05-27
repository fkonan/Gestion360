<div class="container-fluid p-3">
  <h6 class="mb-3">Nuevo documento</h6>

  <div class="card shadow-sm">
    <div class="card-header">
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
          <div class="fw-bold fs-6">Documento nuevo</div>
          <div class="text-muted">El código se asignará al aprobar.</div>
        </div>
        <span class="text-muted small">Se crea en estado EN_REVISIÓN.</span>
      </div>
    </div>

    <div class="card-body">
      <form id="formNuevoDocumento" method="POST" action="{{ route('mapa-procesos.documento.guardar') }}" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">
          <div class="col-12 col-md-4">
            <label class="form-label">Categoría</label>
            <input type="text" id="categoria_label" class="form-control" readonly value="">
          </div>
          <div class="col-12 col-md-4">
            <label for="id_proceso" class="form-label">Proceso</label>
            <select name="id_proceso" id="id_proceso" class="form-select" required>
              <option value="">Seleccione</option>
              @foreach($procesos as $proceso)
                <option value="{{ $proceso->id }}" data-categoria="{{ $proceso->categoria }}" data-abreviatura="{{ $proceso->abreviatura }}">
                  {{ $proceso->nombre }}
                </option>
              @endforeach
            </select>
            <span class="error text-danger fw-bold" id="error-id_proceso"></span>
          </div>
          <div class="col-12 col-md-4">
            <label for="id_tipo_doc" class="form-label">Tipo de documento</label>
            <select name="id_tipo_doc" id="id_tipo_doc" class="form-select" required>
              <option value="">Seleccione</option>
              @foreach($tiposDocumentos as $tipo)
                <option value="{{ $tipo->id }}" data-abreviatura="{{ $tipo->abreviatura }}">
                  {{ $tipo->nombre }}
                </option>
              @endforeach
            </select>
            <span class="error text-danger fw-bold" id="error-id_tipo_doc"></span>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Código</label>
            <input type="text" id="codigo_preview" class="form-control" readonly value="Sin asignar">
          </div>
          <div class="col-12 col-md-8">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" name="nombre" id="nombre" class="form-control" required>
            <span class="error text-danger fw-bold" id="error-nombre"></span>
          </div>
          <div class="col-12">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="3" required></textarea>
            <span class="error text-danger fw-bold" id="error-descripcion"></span>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Fecha elaboración</label>
            <input type="text" class="form-control" readonly value="{{ now()->toDateString() }}">
          </div>
          <div class="col-12 col-md-8">
            <label for="centros_costos" class="form-label">Áreas que pueden visualizar (opcional)</label>
            <select name="centros_costos[]" id="centros_costos" class="form-select select2" multiple>
              @foreach($centrosCostos as $centro)
                <option value="{{ $centro->codigo }}">
                  {{ $centro->codigo }} - {{ $centro->descripcion }}
                </option>
              @endforeach
            </select>
            <div class="form-text">Si no selecciona ninguno, el documento será visible para todos.</div>
            <span class="error text-danger fw-bold" id="error-centros_costos"></span>
          </div>
          <div class="col-12 col-md-4">
            <label for="id_ubicacion" class="form-label">Ubicación</label>
            <select name="id_ubicacion" id="id_ubicacion" class="form-select" required>
              <option value="">Seleccione</option>
              @foreach($ubicacionesDocumento as $ubicacion)
                <option value="{{ $ubicacion->id }}">{{ $ubicacion->nombre }}</option>
              @endforeach
            </select>
            <span class="error text-danger fw-bold" id="error-id_ubicacion"></span>
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
          <div class="col-12">
            <label for="comentario_revision" class="form-label">Comentario de la solicitud</label>
            <textarea name="comentario_revision" id="comentario_revision" class="form-control" rows="4" placeholder="Describe brevemente el motivo o los ajustes solicitados"></textarea>
            <span class="error text-danger fw-bold" id="error-comentario_revision"></span>
          </div>
          <div class="col-12">
            <div class="sig-upload-panel">
              <div class="sig-upload-panel__grid">
                <div class="sig-upload-panel__field">
                  <div class="sig-upload-panel__field-head">
                    <label for="archivo" class="form-label mb-0">Archivo (PDF o Word)</label>
                  </div>
                  <input type="file" name="archivo" id="archivo" class="form-control" accept=".pdf,.doc,.docx" required>
                  <div class="sig-upload-panel__meta"></div>
                  <span class="error text-danger fw-bold" id="error-archivo"></span>
                </div>
                <div class="sig-upload-panel__field sig-upload-panel__field--pages">
                  <div class="sig-upload-panel__field-head">
                    <label for="paginas" class="form-label mb-0">Páginas</label>
                    <span class="sig-upload-panel__status sig-upload-panel__status--pending" id="paginas-estado">Auto</span>
                  </div>
                  <input type="number" name="paginas" id="paginas" class="form-control sig-upload-panel__input" min="1" placeholder="Se completa al cargar" readonly>
                  <div class="form-text" id="paginas-ayuda">Se completa al cargar.</div>
                  <span class="error text-danger fw-bold" id="error-paginas"></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-4 d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="submit" class="btn btn-primary">Guardar documento</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  (() => {
    const procesoSelect = document.getElementById('id_proceso');
    const tipoSelect = document.getElementById('id_tipo_doc');
    const categoriaInput = document.getElementById('categoria_label');
    const codigoPreview = document.getElementById('codigo_preview');
    if (!procesoSelect || !categoriaInput || !tipoSelect || !codigoPreview) return;

    const categorias = {
      G: 'Procesos Gerenciales',
      M: 'Procesos Misionales',
      A: 'Procesos de Apoyo',
      O: 'Otros Procesos'
    };

    const actualizarCategoria = () => {
      const option = procesoSelect.options[procesoSelect.selectedIndex];
      const codigo = option ? option.getAttribute('data-categoria') : '';
      categoriaInput.value = categorias[codigo] || '';
    };

    const actualizarCodigo = () => {
      const optionProceso = procesoSelect.options[procesoSelect.selectedIndex];
      const optionTipo = tipoSelect.options[tipoSelect.selectedIndex];
      const abrevProceso = optionProceso ? optionProceso.getAttribute('data-abreviatura') : '';
      const abrevTipo = optionTipo ? optionTipo.getAttribute('data-abreviatura') : '';

      if (abrevProceso && abrevTipo) {
        codigoPreview.value = `${String(abrevTipo).toUpperCase()}-${String(abrevProceso).toUpperCase()}-`;
      } else {
        codigoPreview.value = 'Sin asignar';
      }
    };

    procesoSelect.addEventListener('change', () => {
      actualizarCategoria();
      actualizarCodigo();
    });
    tipoSelect.addEventListener('change', actualizarCodigo);
    actualizarCategoria();
    actualizarCodigo();
  })();
</script>

@include('sig::_paginas_automaticas')

