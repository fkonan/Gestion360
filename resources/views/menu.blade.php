<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

        <li class="nav-item has-treeview">
            <a style="background-color:#EAE9E9; color:#6c757d; font-size:16px;" href="#" class="nav-link">
                <i class="nav-icon fas fa-cog" style="color: #0E2146;"></i>
                <p><b>Configuración</b><i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a style="color:#6c757d;" class="nav-link">
                        <i class="nav-icon fas fa-desktop"></i>
                        <p><b>Gestión Sistema</b></p>
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-item has-treeview">
            <a style="background-color:#EAE9E9; color:#6c757d; font-size:16px;" href="#" class="nav-link">
                <i class="nav-icon fas fa-user-tie" style="color: #0E2146;"></i>
                <p><b>Administración</b><i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a style="color:#6c757d;" href="{{ route('persona.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-people-arrows"></i>
                        <p><b>Personas</b></p>
                    </a>
                </li>
                <li class="nav-item">
                    <a style="color:#6c757d;" href="{{ route('usuarios.index') }}" class="nav-link">
                        <i class="nav-icon fas fa-user-tie"></i>
                        <p><b>Usuarios</b></p>
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-item">
            <a style="background-color:#EAE9E9; color:#6c757d;" href="{{ route('formatos.index') }}" class="nav-link">
                <i class="nav-icon fas fa-map" style="color: #0E2146;"></i>
                <p><b>Procesos</b></p>
            </a>
        </li>

        <li class="nav-item has-treeview">
            <a style="background-color:#D0CCFA; color:#000000; font-size:16px;" href="#" class="nav-link">
                <i class="nav-icon fas fa-cloud"></i>
                <p><b>Sesión</b><i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="#" onclick="document.getElementById('logoutForm').submit();" class="nav-link bg-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <p>Cerrar sesión</p>
                    </a>
                    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</nav>