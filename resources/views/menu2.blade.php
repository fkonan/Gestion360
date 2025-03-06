<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
        @foreach($modulos as $modulo)
            <li class="nav-item has-treeview">
                <a  href="{{ $modulo->ModRuta ? route($modulo->ModRuta) : '#' }}" style="background-color:#EAE9E9;" class="nav-link nav-link text-secondary">
                    <i class="nav-icon fas {{ $modulo->ModIcono  }}" style="color: #0E2146;"></i>
                    <p><b>{{ Str::title($modulo->ModNom) }}</b></p>
                    @if($modulo->submodulos->count())   
                        <p><i class="right fas fa-angle-left"></i></p>
                    @endif
                </a>
                @if($modulo->submodulos->count())
                    <ul class="nav nav-treeview">
                        @foreach($modulo->submodulos as $submodulo)
                            <li class="nav-item ms-3">
                                <a class="nav-link text-black" href="{{ $submodulo->ModRuta ? route($submodulo->ModRuta) : '#' }}">
                                    <i class="nav-icon fas {{ $submodulo->ModIcono }}" style="color: #0E2146;"></i>
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


