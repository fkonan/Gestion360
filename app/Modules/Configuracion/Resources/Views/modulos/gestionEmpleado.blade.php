@extends('layouts.dashboard')

@section('title', 'Gestion empleados')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestion Empleado'],
    ]" />
@endsection

@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
  <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-2">
    <!-- <div class="col">
      <x-card
        color="bg-success"
        titulo="Solicitudes RRHH"
        descripcion="Solicitudes y novedades"
        icono="fa-clipboard-list"
        ruta="{{ route('gestionRRHH.solicitudes.index') }}" />
    </div> -->

    <div class="col"
      onclick="cargarModal(`{{ route('conductor.descanso') }}`, 'Aqui puede registrar eventos no reportados de descanso de conductores','#descansoConductorForm','modal-md')">
      <x-card
        color="bg-secondary"
        titulo="Descanso Conductores"
        descripcion="Gestion"
        icono="fas fa-bed"
        ruta="#" />
    </div>

    <div class="col"
      onclick="cargarModal(`{{ route('conductor.preoperacional.index') }}`, 'Aqui puede levantar bloqueos por revision de preoperacionales','#preoperacionalForm','modal-md')">
      <x-card
        color="bg-danger"
        titulo="Revision preoperacional"
        descripcion="Novedad"
        icono="fas fa-clipboard-check"
        ruta="#" />
    </div>

    <div class="col"
      onclick="cargarModal(`{{ route('politicas.index') }}`, 'Politicas firmadas por el empleado','#formPoliticas','modal-lg')">
      <x-card
        color="bg-success"
        titulo="Firmas empleados"
        descripcion="Politicas"
        icono="fa-signature"
        ruta="#" />
    </div>
  </div>
</div>
@endsection

@pushOnce('script')
@vite(['resources/js/cargarModal.js'])
<script>
  function habilitarOpciones() {
    const opciones = document.getElementById('opciones');
    const opcionEvento = document.getElementById('opcionEvento');
    const opcionEventoLabel = document.getElementById('opcionEventoLabel');
    const evento = document.getElementById('evento');

    opciones.style.display = 'block';
    opcionEvento.style.display = 'block';

    if (evento.value == '50') {
      opcionEventoLabel.innerHTML = 'Fecha de salida a descanso';
    } else {
      opcionEventoLabel.innerHTML = 'Fecha de reingreso de descanso';
    }
  }

  function buscarUltimoEvento() {
    const identificacion = document.getElementById('identificacion').value.trim();
    const ultimoEventoSection = document.getElementById('ultimoEventoSection');
    const ultimoEventoContent = document.getElementById('ultimoEventoContent');

    if (identificacion === '') {
      ultimoEventoSection.style.display = 'none';
      return;
    }

    ultimoEventoSection.style.display = 'block';
    ultimoEventoContent.innerHTML = `
            <div class="d-flex align-items-center text-muted">
                <div class="spinner-border spinner-border-sm me-2" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                Verificando conductor...
            </div>
        `;

    fetch(`{{ route('obtener.ultimo.evento') }}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        identificacion: identificacion
      })
    })
      .then(response => response.json())
      .then(data => {
        if (data.nombre && data.evento && data.fecha) {
          ultimoEventoContent.innerHTML = `
                    <div class="mb-2">
                        <strong class="d-block mb-1">
                            <i class="fas fa-user me-1"></i>
                            ${data.nombre}
                        </strong>
                    </div>
                    <div class="mb-1">
                        <span class="badge bg-success fs-6 d-block">
                            ${data.evento}
                        </span>
                    </div>
                    <div>
                        <small class="text-muted d-block">
                            <i class="fas fa-calendar-clock me-1"></i>
                            ${data.fecha}
                        </small>
                    </div>
                `;

          const opcion49 = document.getElementById('opcion-49');
          const opcion50 = document.getElementById('opcion-50');
          const opcion25 = document.getElementById('opcion-25');
          const selectEvento = document.getElementById('evento');

          [opcion49, opcion50, opcion25].forEach(opt => opt.style.display = 'block');
          selectEvento.value = '0';

          if (data.evento === 'REGRESO DE DESCANSO' || data.evento === 'REINTEGRO DE DESCANSO') {
            opcion49.style.display = 'none';
            opcion25.style.display = 'none';
          } else if (data.evento === 'SALIDA A DESCANSO') {
            opcion50.style.display = 'none';
          }
        } else {
          ultimoEventoContent.innerHTML = `
                    <div class="text-muted text-center py-2">
                        <i class="fas fa-search me-1"></i>
                        No se encontraron eventos de descanso para esta identificacion
                    </div>
                `;

          const opcion49 = document.getElementById('opcion-49');
          const opcion50 = document.getElementById('opcion-50');
          const opcion25 = document.getElementById('opcion-25');

          opcion49.style.display = 'none';
          opcion25.style.display = 'none';
          opcion50.style.display = 'block';
        }
      })
      .catch(() => {
        ultimoEventoContent.innerHTML = `
                <div class="text-danger text-center py-2">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Error al consultar la informacion
                </div>
            `;
      });
  }

  let debounceTimer;

  function buscarPersona(input) {
    clearTimeout(debounceTimer);

    document.getElementById('infoPersona').style.display = 'none';
    document.getElementById('noDataMessage').style.display = 'none';

    debounceTimer = setTimeout(() => {
      let identificacion = input.value.trim();
      if (identificacion === '') {
        return;
      }

      let url = input.dataset.url.replace('ID_PLACEHOLDER', identificacion);

      fetch(url)
        .then(res => res.json())
        .then(data => {
          if (data.success && data.data) {
            document.getElementById('nombresInfo').textContent = data.data.nombres || 'No disponible';
            document.getElementById('apellidosInfo').textContent = data.data.apellidos || 'No disponible';
            document.getElementById('infoPersona').style.display = 'block';
            document.getElementById('noDataMessage').style.display = 'none';
          } else {
            document.getElementById('infoPersona').style.display = 'none';
            document.getElementById('noDataMessage').style.display = 'block';
          }
        })
        .catch(() => {
          document.getElementById('infoPersona').style.display = 'none';
          document.getElementById('noDataMessage').style.display = 'block';
        });
    }, 500);
  }
</script>
@endPushOnce
