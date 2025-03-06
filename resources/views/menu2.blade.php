<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
        @foreach($modulos as $modulo)
            <li class="nav-item has-treeview">
                <a style="background-color:#EAE9E9; color:#6c757d; font-size:16px;" href="{{ $modulo->route ? route($modulo->route) : '#' }}" class="nav-link">
                    <i class="nav-icon fas {{ $modulo->ModIcono  }}" style="color: #0E2146;"></i>
                    <p><b>{{ Str::title($modulo->ModNom) }}</b></p>
                    @if($modulo->submodulos->count())   
                        <i class="right fas fa-angle-left"></i></p>
                    @endif
                </a>
                @if($modulo->submodulos->count())
                    <ul class="nav nav-treeview">
                        @foreach($modulo->submodulos as $submodulo)
                            <li class="nav-item">
                                <a class="nav-link text-black" href="{{ $hijo->route ? route($hijo->route) : '#' }}">
                                    <p>{{ Str::title($submodulo->ModNom) }}</p>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
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


