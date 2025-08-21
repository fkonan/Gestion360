<!-- Modal solicitud nuevo ingreso empleado -->
<form
    id="formNuevoIngreso" 
    action="{{ route('gestion.nuevoIngreso') }}" 
    method="POST" 
    enctype="multipart/form-data"
    onsubmit="deshabilitarSubmit(this)">
    
    @csrf
    <div class="p-3" style="max-width: 500px;">
        
        <!-- Número de identificación -->
        <div class="mb-3">
            <label for="identificacion" class="form-label">Número de identificación</label>
            <input type="number" name="identificacion" id="identificacion" class="form-control" required>
            <span class="error text-danger fw-bold" id="error-identificacion"></span>
        </div>

        <!-- Nombres -->
        <div class="mb-3">
            <label for="nombres" class="form-label">Nombres</label>
            <input type="text" name="nombres" id="nombres" class="form-control" required>
            <span class="error text-danger fw-bold" id="error-nombres"></span>
        </div>

        <!-- Apellidos -->
        <div class="mb-3">
            <label for="apellidos" class="form-label">Apellidos</label>
            <input type="text" name="apellidos" id="apellidos" class="form-control" required>
            <span class="error text-danger fw-bold" id="error-apellidos"></span>
        </div>

        <!-- Correo electrónico -->
        <div class="mb-3">
            <label for="email" class="form-label">Correo electrónico</label>
            <input type="email" name="email" id="email" class="form-control" required>
            <span class="error text-danger fw-bold" id="error-email"></span>
        </div>

        <!-- Botones -->
        <div class="pt-3">
            <button type="submit" class="btn btn-success">Registrar</button>
            <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cancelar</button>
        </div>

    </div>
</form>
