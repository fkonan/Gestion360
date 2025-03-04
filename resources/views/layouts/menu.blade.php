<div class="d-flex flex-column flex-shrink-0 p-3 pt-4 text-white" style="width: 250px; height: 100vh; position: fixed; background-color:#0A57A7">

    <div class="text-center mb-3">
        <strong class="text-light d-block">{{ auth()->user()->persona->PerNombres }}</strong>
        <strong class="text-light d-block">{{ auth()->user()->persona->PerApellidos }}</strong>
    </div>

    <div class="reloj text-center text-black" style="display: flex; justify-content: center;" onload="actualizarReloj()">
        <h6 id="horas" class="horas"></h6>
        <h6>:</h6>
        <h6 id="minutos" class="minutos"></h6>
        <h6>:</h6>
        <h6 id="segundos" class="segundos"></h6>&nbsp
        <h6 id="ampm" class="ampm"></h6>
    </div>
    <hr>

    <ul class="nav flex-column text-left px-3 mb-auto">
        <li class="nav-item">
            <a href="{{ url('/') }}" class="nav-link text-white border mb-2 rounded">
                Inicio
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('formatos.index') }}" class="nav-link text-white border mb-2 rounded">
                Mapa de procesos
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('persona.index') }}" class="nav-link text-white border mb-2 rounded">
                Personas
            </a>
        </li>
    </ul>

    <div class="mt-auto">   
        <form action="{{ route('logout') }}" method="POST" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-light text-dark fw-bold btn-sm w-100">Cerrar sesión</button>
        </form>
    </div>

</div>

