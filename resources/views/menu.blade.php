<nav class="mt-2">
    <!-- clase ocultar sub modulos al cerrar: nav-collapse-hide-child  -->
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

        <!-- Modulos principales -->
        @foreach($modulos as $modulo)
        
            <!-- Modulos permiso de visualizacion -->
            @if(! $modulo->ModPermiso || auth()->user()->can($modulo->ModPermiso))
            <li class="nav-item has-treeview">
                <a  href="{{ $modulo->ModRuta ? route($modulo->ModRuta) : '#' }}" 
                    class="nav-link text-dark"
                    style="background-color: #D6D6D6">

                    <i class="nav-icon fas {{ $modulo->ModIcono  }}" style="color: #0E2146;"></i>
                    <p><b>{{ $modulo->nombre_formateado }}</b></p>
                    @if($modulo->submodulos->count())   
                        <p><i class="right fas fa-angle-left"></i></p>
                    @endif
                </a>

                <!-- Submodulos -->
                @if($modulo->submodulos->count())
                <ul class="nav nav-treeview">
                    @foreach($modulo->submodulos as $submodulo)

                        @if ($submodulo->ModEstado !== 'ACTIVO')
                            @continue
                        @endif

                        <!-- Submodulos permisos -->
                        @if(! $submodulo->ModPermiso || auth()->user()->can($submodulo->ModPermiso))     
                        <li class="nav-item">
                            <a class="nav-link text-black"  href="{{ $submodulo->ModRuta ? route($submodulo->ModRuta) : '#' }}">
                                <i class="nav-icon fas {{ $submodulo->ModIcono }} " style="color: #0E2146;"></i>
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
            <a style="background-color:#D0CCFA; color:#000000; font-size:16px;" href="#" class="nav-link">
                <i class="nav-icon fas fa-cloud"></i>
                <p><b>Sesion</b></p>
                <p><i class="right fas fa-angle-left"></i></p>
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


