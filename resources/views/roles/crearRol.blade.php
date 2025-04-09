<!-- Modal para crear un nuevo rol -->
<div class="container-fluid p-4 bg-white border rounded">
    <form id="formCrearRol" action="{{ route('roles.store') }}" method="POST">
        @csrf
        <div class="px-3">
            <div class="mb-3">
                <label for="name" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="name" name="name" required>
                <span class="error text-danger fw-bold" id="error-name"></span>
            </div>
            <button type="submit" class="btn btn-success">Guardar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>
    </form>
</div>
