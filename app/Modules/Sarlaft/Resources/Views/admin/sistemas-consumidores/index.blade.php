@extends('layouts.dashboard')

@section('title', 'Sistemas Consumidores')

@section('content')

{{-- Banner secret generado (se muestra una sola vez) --}}
@if(session('nuevo_client_secret'))
<div class="alert alert-warning border border-warning shadow mb-4" role="alert">
    <h6 class="fw-bold mb-2">
        <i class="bi bi-exclamation-triangle-fill me-1"></i>
        Guarde estas credenciales — el secret NO se volverá a mostrar
    </h6>
    <div class="mb-1">
        <span class="fw-semibold">Client ID:</span>
        <code id="banner-client-id">{{ session('nuevo_client_id') }}</code>
    </div>
    <div class="mb-2">
        <span class="fw-semibold">Client Secret:</span>
        <code id="banner-client-secret">{{ session('nuevo_client_secret') }}</code>
        <button type="button" class="btn btn-sm btn-outline-dark ms-2" onclick="copiarSecret()">
            <i class="bi bi-clipboard"></i> Copiar
        </button>
        <span id="banner-copiado" class="text-success ms-2 d-none small">¡Copiado!</span>
    </div>
    <p class="mb-0 small text-muted">
        <i class="bi bi-shield-lock me-1"></i>
        Entregue estas credenciales a la empresa por un canal seguro.
    </p>
</div>
@endif

<div class="row g-4">
    {{-- Formulario nuevo sistema --}}
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-plus-lg"></i> Nuevo Sistema</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('sarlaft.sistemas-consumidores.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required>
                        @error('nombre') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Codigo <span class="text-danger">*</span></label>
                        <input type="text" name="codigo" id="codigo" class="form-control @error('codigo') is-invalid @enderror" value="{{ old('codigo') }}" required placeholder="ej: logtrans">
                        @error('codigo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="mb-3">
                        <label for="limite_requests_minuto" class="form-label">Limite req/min <span class="text-danger">*</span></label>
                        <input type="number" name="limite_requests_minuto" id="limite_requests_minuto" class="form-control @error('limite_requests_minuto') is-invalid @enderror" value="{{ old('limite_requests_minuto', 100) }}" min="1" max="10000" required>
                        @error('limite_requests_minuto') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label for="modo_integracion" class="form-label">Modo de integración <span class="text-danger">*</span></label>
                        <select name="modo_integracion" id="modo_integracion" class="form-select @error('modo_integracion') is-invalid @enderror" required>
                            <option value="pull"     {{ old('modo_integracion', 'pull') === 'pull'     ? 'selected' : '' }}>Pull (HTTP)</option>
                            <option value="push"     {{ old('modo_integracion') === 'push'             ? 'selected' : '' }}>Push (recibe datos)</option>
                            <option value="db"       {{ old('modo_integracion') === 'db'               ? 'selected' : '' }}>Lectura directa de BD</option>
                            <option value="consulta" {{ old('modo_integracion') === 'consulta'         ? 'selected' : '' }}>Consulta (JWT)</option>
                        </select>
                        @error('modo_integracion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Bloque Pull --}}
                    <div id="bloque-pull">
                        <p class="text-muted small mb-2"><i class="bi bi-arrow-down-circle"></i> Configuración Pull</p>
                        <div class="mb-3">
                            <label for="pull_endpoint" class="form-label">Endpoint Pull</label>
                            <input type="url" name="pull_endpoint" id="pull_endpoint" class="form-control @error('pull_endpoint') is-invalid @enderror" value="{{ old('pull_endpoint') }}" placeholder="https://api.empresa.com/intentos">
                            <div class="form-text">URL que SARLAFT consultará para obtener los intentos.</div>
                            @error('pull_endpoint') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="pull_token" class="form-label">Token Pull</label>
                            <input type="text" name="pull_token" id="pull_token" class="form-control @error('pull_token') is-invalid @enderror" value="{{ old('pull_token') }}" placeholder="Bearer token del sistema externo">
                            @error('pull_token') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Bloque DB --}}
                    <div id="bloque-db" class="d-none">
                        <p class="text-muted small mb-2"><i class="bi bi-database"></i> Configuración lectura de BD</p>
                        <div class="mb-3">
                            <label for="db_conexion" class="form-label">Conexión Laravel <span class="text-danger">*</span></label>
                            <input type="text" name="db_conexion" id="db_conexion" class="form-control @error('db_conexion') is-invalid @enderror" value="{{ old('db_conexion') }}" placeholder="ej: oracle">
                            <div class="form-text">Nombre de conexión definida en <code>config/database.php</code>.</div>
                            @error('db_conexion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="db_tabla" class="form-label">Tabla <span class="text-danger">*</span></label>
                            <input type="text" name="db_tabla" id="db_tabla" class="form-control @error('db_tabla') is-invalid @enderror" value="{{ old('db_tabla') }}" placeholder="ej: LOGTRANSPRO.SARLAFT_OPERACIONES">
                            <div class="form-text">Tabla o vista en el esquema externo (incluye schema si aplica).</div>
                            @error('db_tabla') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="db_filtro_sistema_origen" class="form-label">Filtro sistema origen</label>
                            <input type="text" name="db_filtro_sistema_origen" id="db_filtro_sistema_origen" class="form-control @error('db_filtro_sistema_origen') is-invalid @enderror" value="{{ old('db_filtro_sistema_origen') }}" placeholder="ej: Logtrans">
                            <div class="form-text">Valor para filtrar por columna <code>sistema_origen</code>. Opcional.</div>
                            @error('db_filtro_sistema_origen') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    {{-- Bloque Consulta (JWT) --}}
                    <div id="bloque-consulta" class="d-none">
                        <p class="text-muted small mb-2"><i class="bi bi-key"></i> Configuración Consulta JWT</p>
                        <div class="mb-3">
                            <label for="scopes" class="form-label">Scopes</label>
                            <input type="text" name="scopes" id="scopes"
                                   class="form-control @error('scopes') is-invalid @enderror"
                                   value="{{ old('scopes', 'sarlaft.listas.consultar') }}"
                                   placeholder="sarlaft.listas.consultar">
                            <div class="form-text">Permisos separados por espacio. Para consulta de listas: <code>sarlaft.listas.consultar</code></div>
                            @error('scopes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="alert alert-info small mb-0 py-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Al crear el sistema se generará un <strong>client_secret</strong> que se mostrará
                            <strong>una sola vez</strong>. El <strong>client_id</strong> será el código del sistema.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">
                        <i class="bi bi-plus-lg"></i> Crear Sistema
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Tabla sistemas --}}
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre / Codigo</th>
                                <th>Estado</th>
                                <th>Limite/min</th>
                                <th>Token API</th>
                                <th>Modo</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sistemas as $sistema)
                            @php $modo = $sistema->modo_integracion ?? 'pull'; @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $sistema->nombre }}</div>
                                    <code class="small text-muted">{{ $sistema->codigo }}</code>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $sistema->estado === 'activo' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($sistema->estado) }}
                                    </span>
                                </td>
                                <td>{{ $sistema->limite_requests_minuto }}</td>
                                <td>
                                    <code class="user-select-all small">{{ substr($sistema->api_token ?? '', 0, 16) }}...</code>
                                </td>
                                <td>
                                    @if($modo === 'push')
                                        <span class="badge bg-warning text-dark">
                                            <i class="bi bi-arrow-up-circle"></i> Push
                                        </span>
                                    @elseif($modo === 'db')
                                        <span class="badge bg-info text-dark"
                                              title="{{ $sistema->db_conexion }}: {{ $sistema->db_tabla }}">
                                            <i class="bi bi-database"></i> BD
                                        </span>
                                    @elseif($modo === 'consulta')
                                        <span class="badge bg-dark">
                                            <i class="bi bi-key"></i> Consulta (JWT)
                                        </span>
                                    @else
                                        @if($sistema->pull_endpoint)
                                            <span class="badge bg-primary" title="{{ $sistema->pull_endpoint }}">
                                                <i class="bi bi-arrow-down-circle"></i> Pull
                                            </span>
                                        @else
                                            <span class="badge bg-light text-secondary border">
                                                <i class="bi bi-arrow-down-circle"></i> Pull
                                            </span>
                                        @endif
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button type="button"
                                        class="btn btn-sm btn-outline-primary me-1"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modalEditarSistema"
                                        data-id="{{ $sistema->id }}"
                                        data-nombre="{{ $sistema->nombre }}"
                                        data-limite="{{ $sistema->limite_requests_minuto }}"
                                        data-modo="{{ $modo }}"
                                        data-endpoint="{{ $sistema->pull_endpoint }}"
                                        data-token="{{ $sistema->pull_token }}"
                                        data-db-conexion="{{ $sistema->db_conexion }}"
                                        data-db-tabla="{{ $sistema->db_tabla }}"
                                        data-db-filtro="{{ $sistema->db_filtro_sistema_origen }}"
                                        data-scopes="{{ $sistema->scopes }}"
                                        title="Editar configuración">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    @if($modo === 'consulta')
                                    <form action="{{ route('sarlaft.sistemas-consumidores.regenerar-secret', $sistema) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('¿Regenerar el secret? El actual dejará de funcionar.')">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-warning me-1" title="Regenerar secret">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                    @endif

                                    <form action="{{ route('sarlaft.sistemas-consumidores.destroy', $sistema) }}" method="POST" class="d-inline" onsubmit="return confirm('Eliminar sistema {{ $sistema->nombre }}?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay sistemas registrados.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($sistemas->hasPages())
            <div class="card-footer">
                {{ $sistemas->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

{{-- Modal editar sistema --}}
<div class="modal fade" id="modalEditarSistema" tabindex="-1" aria-labelledby="modalEditarSistemaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditarSistema" method="POST">
                @csrf
                @method('PATCH')
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarSistemaLabel">
                        <i class="bi bi-pencil"></i> Editar sistema: <span id="modalNombreSistema"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_limite" class="form-label">Limite req/min <span class="text-danger">*</span></label>
                        <input type="number" name="limite_requests_minuto" id="edit_limite" class="form-control" min="1" max="10000" required>
                    </div>

                    <hr class="my-3">

                    <div class="mb-3">
                        <label for="edit_modo" class="form-label">Modo de integración <span class="text-danger">*</span></label>
                        <select name="modo_integracion" id="edit_modo" class="form-select" required>
                            <option value="pull">Pull (HTTP)</option>
                            <option value="push">Push (recibe datos)</option>
                            <option value="db">Lectura directa de BD</option>
                            <option value="consulta">Consulta (JWT)</option>
                        </select>
                    </div>

                    {{-- Bloque Pull (modal) --}}
                    <div id="edit-bloque-pull">
                        <p class="text-muted small mb-2"><i class="bi bi-arrow-down-circle"></i> Configuración Pull</p>
                        <div class="mb-3">
                            <label for="edit_pull_endpoint" class="form-label">Endpoint Pull</label>
                            <input type="url" name="pull_endpoint" id="edit_pull_endpoint" class="form-control" placeholder="https://api.empresa.com/intentos">
                            <div class="form-text">URL que SARLAFT consultará. Dejar vacío para deshabilitar Pull.</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_pull_token" class="form-label">Token Pull</label>
                            <input type="text" name="pull_token" id="edit_pull_token" class="form-control" placeholder="Bearer token del sistema externo">
                            <div class="form-text">Se envía como <code>Authorization: Bearer …</code> al hacer el Pull.</div>
                        </div>
                    </div>

                    {{-- Bloque DB (modal) --}}
                    <div id="edit-bloque-db" class="d-none">
                        <p class="text-muted small mb-2"><i class="bi bi-database"></i> Configuración lectura de BD</p>
                        <div class="mb-3">
                            <label for="edit_db_conexion" class="form-label">Conexión Laravel <span class="text-danger">*</span></label>
                            <input type="text" name="db_conexion" id="edit_db_conexion" class="form-control" placeholder="ej: oracle">
                            <div class="form-text">Nombre de conexión definida en <code>config/database.php</code>.</div>
                        </div>
                        <div class="mb-3">
                            <label for="edit_db_tabla" class="form-label">Tabla <span class="text-danger">*</span></label>
                            <input type="text" name="db_tabla" id="edit_db_tabla" class="form-control" placeholder="ej: LOGTRANSPRO.SARLAFT_OPERACIONES">
                        </div>
                        <div class="mb-3">
                            <label for="edit_db_filtro" class="form-label">Filtro sistema origen</label>
                            <input type="text" name="db_filtro_sistema_origen" id="edit_db_filtro" class="form-control" placeholder="ej: Logtrans">
                        </div>
                    </div>

                    {{-- Bloque Consulta (modal) --}}
                    <div id="edit-bloque-consulta" class="d-none">
                        <p class="text-muted small mb-2"><i class="bi bi-key"></i> Configuración Consulta JWT</p>
                        <div class="mb-3">
                            <label for="edit_scopes" class="form-label">Scopes</label>
                            <input type="text" name="scopes" id="edit_scopes" class="form-control"
                                   placeholder="sarlaft.listas.consultar">
                            <div class="form-text">Permisos separados por espacio.</div>
                        </div>
                        <div class="alert alert-secondary small mb-0 py-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Para regenerar el secret usá el botón <strong>Regenerar secret</strong>
                            (<i class="bi bi-arrow-repeat"></i>) en la tabla.
                        </div>
                    </div>

                    <div class="alert alert-info small mb-0 mt-3">
                        <i class="bi bi-info-circle"></i>
                        En modo <strong>Pull</strong>, el comando <code>sarlaft:pull-intentos</code> consultará el endpoint enviando
                        <code>?fecha_desde=…&fecha_hasta=…</code> y esperará <code>{ "data": [ ... ] }</code>.<br>
                        En modo <strong>Lectura de BD</strong>, leerá directamente la tabla Oracle configurada (sin endpoint HTTP).<br>
                        En modo <strong>Consulta (JWT)</strong>, la empresa autentica con client_id + client_secret para verificar listas.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('script')
<script>
    // --- Copiar secret del banner ---
    function copiarSecret() {
        const secretEl = document.getElementById('banner-client-secret');
        const copiadoEl = document.getElementById('banner-copiado');
        if (!secretEl) { return; }
        navigator.clipboard.writeText(secretEl.textContent.trim()).then(function () {
            if (copiadoEl) {
                copiadoEl.classList.remove('d-none');
                setTimeout(function () { copiadoEl.classList.add('d-none'); }, 2500);
            }
        }).catch(function () {
            // Fallback para contextos sin HTTPS
            const range = document.createRange();
            range.selectNode(secretEl);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);
            document.execCommand('copy');
            window.getSelection().removeAllRanges();
        });
    }

    // --- Toggle modo integración (form alta) ---
    (function () {
        const selectModo     = document.getElementById('modo_integracion');
        const bloquePull     = document.getElementById('bloque-pull');
        const bloqueDb       = document.getElementById('bloque-db');
        const bloqueConsulta = document.getElementById('bloque-consulta');

        function toggleBloques(modo) {
            if (!bloquePull || !bloqueDb || !bloqueConsulta) { return; }
            bloquePull.classList.toggle('d-none',     modo !== 'pull');
            bloqueDb.classList.toggle('d-none',       modo !== 'db');
            bloqueConsulta.classList.toggle('d-none', modo !== 'consulta');
        }

        if (selectModo) {
            toggleBloques(selectModo.value);
            selectModo.addEventListener('change', function () {
                toggleBloques(this.value);
            });
        }
    })();

    // --- Modal editar sistema ---
    (function () {
        const modal            = document.getElementById('modalEditarSistema');
        const editModo         = document.getElementById('edit_modo');
        const editPull         = document.getElementById('edit-bloque-pull');
        const editDb           = document.getElementById('edit-bloque-db');
        const editConsulta     = document.getElementById('edit-bloque-consulta');

        function toggleModalBloques(modo) {
            if (!editPull || !editDb || !editConsulta) { return; }
            editPull.classList.toggle('d-none',     modo !== 'pull');
            editDb.classList.toggle('d-none',       modo !== 'db');
            editConsulta.classList.toggle('d-none', modo !== 'consulta');
        }

        if (modal) {
            modal.addEventListener('show.bs.modal', function (event) {
                const btn  = event.relatedTarget;
                const id   = btn?.dataset.id;
                const modo = btn?.dataset.modo || 'pull';

                const nombreEl = document.getElementById('modalNombreSistema');
                if (nombreEl) { nombreEl.textContent = btn?.dataset.nombre || ''; }

                const limiteEl = document.getElementById('edit_limite');
                if (limiteEl) { limiteEl.value = btn?.dataset.limite || ''; }

                if (editModo) {
                    editModo.value = modo;
                }

                const endpointEl = document.getElementById('edit_pull_endpoint');
                const tokenEl    = document.getElementById('edit_pull_token');
                if (endpointEl) { endpointEl.value = btn?.dataset.endpoint || ''; }
                if (tokenEl)    { tokenEl.value    = btn?.dataset.token    || ''; }

                const dbConexionEl = document.getElementById('edit_db_conexion');
                const dbTablaEl    = document.getElementById('edit_db_tabla');
                const dbFiltroEl   = document.getElementById('edit_db_filtro');
                if (dbConexionEl) { dbConexionEl.value = btn?.dataset.dbConexion || ''; }
                if (dbTablaEl)    { dbTablaEl.value    = btn?.dataset.dbTabla    || ''; }
                if (dbFiltroEl)   { dbFiltroEl.value   = btn?.dataset.dbFiltro   || ''; }

                const editScopesEl = document.getElementById('edit_scopes');
                if (editScopesEl) { editScopesEl.value = btn?.dataset.scopes || ''; }

                toggleModalBloques(modo);

                const form = document.getElementById('formEditarSistema');
                if (form && id) {
                    form.action = '{{ url("sarlaft/sistemas-consumidores") }}/' + id;
                }
            });

            if (editModo) {
                editModo.addEventListener('change', function () {
                    toggleModalBloques(this.value);
                });
            }
        }
    })();
</script>
@endpush
