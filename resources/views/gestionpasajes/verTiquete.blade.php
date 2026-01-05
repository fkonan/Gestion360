<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ver Tiquete</title>
</head>
<body class="bg-light">

<div class="container-fluid mt-3">
  <div class="d-flex justify-content-end mb-2">
    <a href="{{ route('descargar-tiquete', $id) }}" class="btn btn-primary" target="_blank">
      <i class="fas fa-download"></i> Descargar PDF
    </a>
  </div>

  <iframe src="{{ route('ver-tiquete', $id) }}"
          style="width:100%; height:90vh; border:none; box-shadow: 0 0 10px rgba(0,0,0,0.2);"></iframe>
</div>
</body>
</html>
