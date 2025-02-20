<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Departamento</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center">Editar Departamento</h1>

        <form action="{{ route('departamentos.update', ['id' => $departamento->IdDepartamento]) }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="IdDepartamento" class="form-label">ID Departamento</label>
                <input type="number" class="form-control" id="IdDepartamento" name="IdDepartamento" value="{{ $departamento->IdDepartamento }}" disabled>
            </div>

            <div class="mb-3">
                <label for="DepNom" class="form-label">Nombre Departamento</label>
                <input type="text" class="form-control" id="DepNom" name="DepNom" value="{{ $departamento->DepNom }}" required>
            </div>

            <div class="mb-3">
                <label for="DepNomMin" class="form-label">Nombre en Minúsculas</label>
                <input type="text" class="form-control" id="DepNomMin" name="DepNomMin" value="{{ $departamento->DepNomMin }}" required>
            </div>

            <button type="submit" class="btn btn-primary">Guardar</button>
        </form>
    </div>
</body>
</html>
