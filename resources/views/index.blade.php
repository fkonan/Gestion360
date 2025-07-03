@extends('layouts.app')

@section('title','index')

@section('content')
<br>
<div class="container pb-5">
    <div class="row mb-5 align-items-center">
        <div class="col-12 col-md-8 mx-auto text-center">
            <h1 class="display-5 fw-bold mb-2 text-tertiary">Panel Principal de Gestión</h1>
            <p class="lead mb-0 text-tertiary">Accede a los módulos principales del sistema</p>
        </div>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-12 col-sm-6 col-lg-4">
            <x-cardIndex
                icono="fa-cubes"
                titulo="Administración del Sistema"
                descripcion="Administra usuarios, roles, permisos y la configuración general del sistema."
                ruta="{{ route('login.form') }}"
            />
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <x-cardIndex
                icono="fa-users"
                titulo="Conductores y Empleados"
                descripcion="Gestión de recursos humanos, incluyendo información, procesos y firma de políticas."
                ruta="http://172.20.32.22/gestion/firmaConductores/public/"
            />
        </div>
        <div class="col-12 col-sm-6 col-lg-4">
            <x-cardIndex
                icono="fa-print"
                titulo="Otros servicios"
                descripcion="Incluye impresión de tiquetes y reporte de salidas de terminal."
                ruta="#"
            />
        </div>
    </div>
</div>
@endsection

@pushOnce('script')
<script>

</script>
@endpushOnce



