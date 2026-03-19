<!-- Resultado AJAX en preoperacionales form  -->
<br>
<div class="container-fluid p-3 border rounded sidebar-dark-primary tableContainer pb-2" style="min-height:150px">

  <!-- Datos adicionales del preoperacional -->
  <div class="mb-2">
    <strong>Documento:</strong> {{ $preoperacional->documento }}
  </div>
  <div class="mb-2">
    <strong>Observacion:</strong> {{ $preoperacional->observacion ?? 'Sin observacion' }}
  </div>
  <div class="mb-2">
    <strong>Fecha registro:</strong> {{ $preoperacional->created_at->format('d/m/Y H:i') }}
  </div>

</div>
