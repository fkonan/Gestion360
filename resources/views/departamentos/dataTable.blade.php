<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Departamentos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center">Lista de Departamentos</h1>
        <div class="row">
            <table class="table table-striped">
            <tr>
                <th>Id Departamento</th>
                <th>Nombre Departamento</th>
                <th>Nombre Departamento minuscula</th>
                <th></th>
                <th></th>
            </tr>
            @foreach($departamentos as $departamento)
            <tr>
                <th>{{ $departamento?->IdDepartamento }}</th>
                <th>{{ $departamento?->DepNom }}</th>
                <th>{{ $departamento?->DepNomMin }}</th>
                <th>
                    <form method='POST' action="{{ route('departamentos.edit', ['id' => $departamento->IdDepartamento]) }}">
                        @csrf
                        <input type="submit" class="bg-primary text-light" value="Actualizar" />
                    </form>
                </th>
                <th>
                    <form method='POST' action="{{ route('departamentos.destroy', ['id' => $departamento->IdDepartamento]) }}">
                        @csrf
                        @method('DELETE')
                        <input type="submit" class="bg-danger text-light" value="Borrar" />
                    </form>
                </th>
            </tr>
            @endforeach
            </table>
          
        </div>
    </div>
</body>
</html>
