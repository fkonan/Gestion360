@extends('layouts.dashboard')

@section('title','Gestión empleados')

@section('breadcrumb')
<x-breadcrumb :items="[
        ['name' => 'Inicio', 'url' => route('home')],
        ['name' => 'Gestión Empleado'],
    ]" />
@endsection
    
@section('content')
<div class="container optionsMenu mt-4 p-0" style="max-width: 1500px;">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-2">
        <div class="col">
            <x-card color="bg-success" 
                    titulo="Incapacidades" 
                    descripcion="Gestión" 
                    icono="fa-procedures" 
                    ruta="{{ route('gestion-empleado.incapacidades') }}"
            />
        </div>
        <div class="col">
            <x-card color="bg-warning" 
                    titulo="Incapacidades" 
                    descripcion="Seguimiento" 
                    icono="fa-phone" 
                    ruta="{{ route('gestion-empleado.seguimiento') }}"
            />
        </div>

        <div class="col" onclick="cargarModal(`{{ route('conductor.descanso') }}`, 'Aqui puede registrar eventos no reportados de descanso de conductores','#descansoConductorForm','modal-md')">
            <x-card color="bg-secondary" 
                    titulo="Descanso Conductores"
                    descripcion="Gestión"
                    icono="fas fa-bed"
                    ruta="#"
            />
        </div>

        <div class="col" onclick="cargarModal(`{{ route('politicas.index') }}`, 'Politicas firmadas por el conductor','#formPoliticas','modal-lg')">
            <x-card color="bg-danger" 
                    titulo="Firmas conductores" 
                    descripcion="Politicas" 
                    icono="fa-signature" 
                    ruta="#"
            />
        </div>
    </div>
</div>
@endsection

@pushOnce('script')
    @vite(['resources/js/cargarModal.js'])
    <script>

    //Habilitar opciones en modal descanso conductor
    function habilitarOpciones() {   
        const opciones = document.getElementById('opciones');
        const opcionEvento = document.getElementById('opcionEvento');
        const opcionEventoLabel = document.getElementById('opcionEventoLabel');
        const evento = document.getElementById('evento');
        
        opciones.style.display = 'block';
        opcionEvento.style.display = 'block';

        if(evento.value == '50'){
            opcionEventoLabel.innerHTML = 'Fecha de salida a descanso';
        }else{
            opcionEventoLabel.innerHTML = 'Fecha de reingreso de descanso';
        }   
    }

    //Ultimo evento registrado - Modal descanso conductor
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
            body: JSON.stringify({ identificacion: identificacion })
        })
        .then(response => response.json())
        .then(data => {
            if (data.nombre && data.evento && data.fecha) {
                ultimoEventoContent.innerHTML = `
                    <div class="mb-2">
                        <strong class="text-dark d-block mb-1">
                            <i class="fas fa-user me-1 text-primary"></i>
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

                // === FILTRAR OPCIONES DEL SELECT SEGÚN EL ÚLTIMO EVENTO ===
                const opcion49 = document.getElementById("opcion-49"); // REGRESO DE DESCANSO
                const opcion50 = document.getElementById("opcion-50"); // SALIDA A DESCANSO
                const opcion25 = document.getElementById("opcion-25"); // REGRESO ANTICIPADO
                const selectEvento = document.getElementById("evento");

                // Mostrar todas antes de filtrar
                [opcion49, opcion50, opcion25].forEach(opt => opt.style.display = "block");
                selectEvento.value = "0"; // Resetear selección

                if (data.evento === "REGRESO DE DESCANSO" || data.evento === "REINTEGRO DE DESCANSO") {
                    // Solo puede salir
                    opcion49.style.display = "none"; // ocultar REGRESO
                    opcion25.style.display = "none"; // ocultar ANTICIPADO
                } else if (data.evento === "SALIDA A DESCANSO") {
                    // Solo puede regresar o reintegrar
                    opcion50.style.display = "none"; // ocultar SALIDA
                }
            } else {
                ultimoEventoContent.innerHTML = `
                    <div class="text-muted text-center py-2">
                        <i class="fas fa-search me-1"></i>
                        No se encontraron eventos de descanso para esta identificación
                    </div>
                `;
            }
        })
        .catch(error => {
            ultimoEventoContent.innerHTML = `
                <div class="text-danger text-center py-2">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    Error al consultar la información
                </div>
            `;
        });
    }

    //Buscar persona por identificación (AJAX) - Nuevo ingreso empleado
    let debounceTimer;
    function buscarPersona(input) {
        clearTimeout(debounceTimer);

        // Ocultar contenedores de información
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
                        // Mostrar información encontrada
                        document.getElementById('nombresInfo').textContent = data.data.nombres || 'No disponible';
                        document.getElementById('apellidosInfo').textContent = data.data.apellidos || 'No disponible';
                        document.getElementById('emailInfo').textContent = data.data.email || 'No disponible';
                        
                        // Mostrar contenedor de información
                        document.getElementById('infoPersona').style.display = 'block';
                        document.getElementById('noDataMessage').style.display = 'none';
                    } else {
                        // Mostrar mensaje de no encontrado
                        document.getElementById('infoPersona').style.display = 'none';
                        document.getElementById('noDataMessage').style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error('Error al buscar persona:', err);
                    // Mostrar mensaje de error
                    document.getElementById('infoPersona').style.display = 'none';
                    document.getElementById('noDataMessage').style.display = 'block';
                });
        }, 500);
    }
    </script>
@endPushOnce