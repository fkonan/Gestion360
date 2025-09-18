<!-- Resultado AJAX en preoperacionales form  -->
<br>
<div class="container-fluid p-3 border rounded sidebar-dark-primary tableContainer pb-2" style="min-height:150px">

    <!-- Datos adicionales del preoperacional -->
    <div class="mb-2">
        <strong>Documento:</strong> {{ $preoperacional->documento }}
    </div>
    <div class="mb-2">
        <strong>Nombre archivo:</strong> {{ $preoperacional->nombre }}
    </div>
    <div class="mb-2">
        <strong>Fecha registro:</strong> {{ $preoperacional->created_at->format('d/m/Y H:i') }}
    </div>

    <!-- Imagen/archivo clickable -->
    <div class="mt-3">
        <img 
            src="data:{{ $preoperacional->mime }};base64,{{ base64_encode($preoperacional->contenido) }}" 
            alt="Adjunto" 
            style="max-width:200px; cursor: pointer; border: 1px solid #ccc; border-radius: 6px; padding: 4px;"
            onclick="abrirArchivo(`{{ route('conductor.preoperacional.archivo', $preoperacional->id) }}`)">
        <div class="small text-muted">Haz clic en la imagen para ampliarla</div>
    </div>

</div>



