<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

        <!-- Modulos principales -->
        @foreach($modulos as $modulo)
        
            <!-- Modulos permisos -->
            @if(!$modulo->ModPermiso || auth()->user()->hasPermissionTo($modulo->ModPermiso))
            @php
            $isParentActive = request()->routeIs($modulo->ModRuta) || $modulo->submodulos->contains(function($submodulo) {
                return request()->routeIs($submodulo->ModRuta) || $submodulo->submodulos->contains(function($submodulo_segundoNivel) {
                    return request()->routeIs($submodulo_segundoNivel->ModRuta);
                });
            });
            @endphp

            <li class="nav-item has-treeview {{ $isParentActive ? 'menu-open' : '' }}">
                <a  href="{{ $modulo->ModRuta ? route($modulo->ModRuta) : '#' }}" style="background-color:#EAE9E9;" class="nav-link text-secondary {{ $isParentActive ? 'active' : '' }}">
                    <i class="nav-icon fas {{ $modulo->ModIcono  }}" style="color: #0E2146;"></i>
                    <p><b>{{ Str::title($modulo->ModNom) }}</b></p>
                    @if($modulo->submodulos->count())   
                        <p><i class="right fas fa-angle-left"></i></p>
                    @endif
                </a>

                <!--Submodulos de primer nivel -->
                @if($modulo->submodulos->count())
                <ul class="nav nav-treeview">
                    @foreach($modulo->submodulos as $submodulo)

                        <!-- Submodulos permisos -->
                        <!--@if(! $submodulo->ModPermiso || auth()->user()->hasPermissionTo($submodulo->ModPermiso))  -->
                        @php
                        $isSubmoduloActive = request()->routeIs($submodulo->ModRuta) || $submodulo->submodulos->contains(function($submodulo_segundoNivel) {
                            return request()->routeIs($submodulo_segundoNivel->ModRuta);
                        });
                        @endphp
                        <li class="nav-item {{ $isSubmoduloActive ? 'menu-open' : '' }}">
                            <a class="nav-link text-black {{ $isSubmoduloActive ? 'active' : '' }}" href="{{ $submodulo->ModRuta ? route($submodulo->ModRuta) : '#' }}">
                                <i class="nav-icon fas {{ $submodulo->ModIcono }} " style="color: #0E2146;"></i>
                                <p>{{ Str::title($submodulo->ModNom) }}</p>
                                @if($submodulo->submodulos->count())   
                                    <p><i class="right fas fa-angle-left"></i></p>
                                @endif
                            </a>

                            <!--Submodulos de segundo nivel -->
                            <!--@if($submodulo->submodulos->count()) -->
                            <ul class="nav nav-treeview">
                                @foreach($submodulo->submodulos as $submodulo_segundoNivel)

                                    <!-- Submodulos permisos -->
                                    @if(! $submodulo_segundoNivel->ModPermiso || auth()->user()->hasPermissionTo($submodulo_segundoNivel->ModPermiso)) 
                                        <li class="nav-item">
                                            <a class="nav-link text-black {{ request()->routeIs($submodulo_segundoNivel->ModRuta) ? 'active' : '' }} sangriaSegundoNivel" 
                                                    href="{{ $submodulo_segundoNivel->ModRuta ? route($submodulo_segundoNivel->ModRuta) : '#' }}">
                                                <i class="nav-icon fas {{ $submodulo_segundoNivel->ModIcono }} " style="color: #0E2146;"></i>
                                                <p>{{ Str::title($submodulo_segundoNivel->ModNom) }}</p>
                                            </a>
                                        </li>
                                    @endif
                                @endforeach
                            </ul>
                            <!--@endif-->
                        </li>
                        <!--@endif-->
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


