<nav class="mt-0 pt-0">
    <!-- clase ocultar sub modulos al cerrar: nav-collapse-hide-child  -->
    <ul class="nav nav-pills nav-sidebar nav-collapse-hide-child flex-column" data-widget="treeview" role="menu">

        <!-- Modulos principales -->
        @foreach($modulos as $modulo)
            <!-- Modulos permiso de visualizacion -->
            @if(! $modulo->ModPermiso || auth()->user()->can($modulo->ModPermiso))
            <li class="nav-item has-treeview">
                <a  href="{{ $modulo->ModRuta && Route::has($modulo->ModRuta) ? route($modulo->ModRuta) : '#' }}" 
                    class="nav-link module text-dark"
                    style="background-color:transparent;">
                    <i class="nav-icon fas {{ $modulo->ModIcono  }}" style="color:rgba(75, 75, 76, 0.7); font-size: 16px;"></i>
                    <p class="fw-medium">{{ $modulo->ModNom }}</p>
                    @if($modulo->submodulos->count())   
                        <p><i class="right fas fa-angle-left"></i></p>
                    @endif
                </a>
                <!-- Submodulos -->
                @if($modulo->submodulos->count())
                <ul class="nav nav-treeview">
                    @foreach($modulo->submodulos as $submodulo)
                        <!-- Submodulos permiso de visualizacion -->
                        @if(! $submodulo->ModPermiso || auth()->user()->can($submodulo->ModPermiso))     
                        <li class="nav-item">
                            <a class="nav-link submodule text-black"  href="{{ $submodulo->ModRuta && Route::has($submodulo->ModRuta) ? route($submodulo->ModRuta) : '#' }}">
                                <i class="nav-icon fas {{ $submodulo->ModIcono }} " style="color:rgba(75, 75, 76, 0.7); font-size: 16px;"></i>
                                <p>{{ Str::title($submodulo->ModNom) }}</p>
                            </a>
                        </li>
                        @endif
                    @endforeach
                </ul>
                @endif
            </li>
            @endif
        @endforeach

        <li class="nav-item has-treeview">
            <a style="font-size:16px;" href="#" class="nav-link">
                <i class="nav-icon fas fa-cloud" style="color:rgba(75, 75, 76, 0.7);"></i>
                <p>Sesión</p>
                <p><i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview">
                <li class="nav-item">
                    <a href="#" onclick="document.getElementById('logoutForm').submit();" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <p class="text-danger">Cerrar sesión</p>
                    </a>
                    <form id="logoutForm" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </li>
    </ul>
</nav>


