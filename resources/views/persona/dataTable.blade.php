<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de personas</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1 class="text-center">Lista de personas</h1>
        <div class="row">
            <table class="table table-striped">
            <tr>
                <th>Id Persona</th>
                <th>Numero de documento</th>
                <th>Apellidos</th>
                <th>Nombres</th>
                <th>Generos</th>
                <th>Estado</th>
            </tr>
            @foreach($personas as $persona)
            <tr>
                <th>{{ $persona?->IdPersona }}</th>
                <th>{{ $persona?->PerNumDoc }}</th>
                <th>{{ $persona?->PerApellidos }}</th>
                <th>{{ $persona?->PerNombres }}</th>
                <th>{{ $persona?->PerGenero }}</th>
                <th>{{ $persona?->PerEstado }}</th>
            </tr>
            @endforeach
            </table>
          
        </div>
    </div>
</body>
</html>
