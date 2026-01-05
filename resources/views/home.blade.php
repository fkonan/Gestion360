@extends('layouts.dashboard')

@section('title', 'Mapa de Procesos')

@section('content')

<div class="container-fluid">

    {{-- Título --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0">Mapa de Procesos</h4>
                <small class="text-muted">Haga clic sobre cada área del mapa para navegar.</small>
            </div>

            <a href="#"
               class="btn btn-sm btn-outline-primary">
                <i class="fas fa-cog me-1"></i> Administrar mapa
            </a>
        </div>
    </div>


    {{-- Contenedor del mapa --}}
    <div class="card shadow-sm">
        <div class="card-body position-relative">

            {{-- El SVG original exacto --}}
            <div class="mapa-svg-container position-relative">
                {!! file_get_contents(public_path('mapa_procesos/mapa_procesos_original.svg')) !!}
            </div>

            {{-- Hotspots invisibles --}}
            <button class="hotspot hotspot-gerenciales"
                    data-area="gerenciales"></button>

            <button class="hotspot hotspot-misionales"
                    data-area="misionales"></button>

            <button class="hotspot hotspot-apoyo"
                    data-area="apoyo"></button>

        </div>
    </div>

</div>

@endsection


@pushOnce('css')
<style>

    /* Contenedor responsive */
    .mapa-svg-container {
        width: 100%;
        max-width: 700px;
        margin: 0 auto;
    }

    .mapa-svg-container svg {
        width: 100%;
        height: auto;
        display: block;
    }

    .hotspot {
        position: absolute;
        background: rgba(255, 255, 255, 0);
        border: none;
        cursor: pointer;
        z-index: 10;
    }

    .hotspot-gerenciales {
        top: 14%;
        left: 31%;
        width: 38%;
        height: 16%;
    }

    .hotspot-misionales {
        top: 40%;
        left: 21%;
        width: 58%;
        height: 19%;
    }

    .hotspot-apoyo {
        top: 68%;
        left: 32%;
        width: 36%;
        height: 16%;
    }

    @media(max-width: 768px){
        .hotspot-gerenciales {
            top: 15%; left: 22%; width: 55%; height: 15%;
        }
        .hotspot-misionales {
            top: 41%; left: 12%; width: 75%; height: 20%;
        }
        .hotspot-apoyo {
            top: 70%; left: 22%; width: 55%; height: 15%;
        }
    }

</style>
@endpushOnce


