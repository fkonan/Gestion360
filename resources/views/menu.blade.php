<nav class="mt-0 pt-0">
  <!-- clase ocultar sub modulos al cerrar: nav-collapse-hide-child  -->
  <ul
    class="nav nav-pills nav-collapse-hide-child nav-sidebar flex-column"
    data-widget="treeview"
    data-animation-speed="200"
    role="menu">
    <!-- Modulos principales -->
    @foreach($modulos as $modulo)
    <!-- Modulos permiso de visualizacion -->
    @if(! $modulo->ModPermiso || auth()->user()->can($modulo->ModPermiso))
    <li class="nav-item has-treeview">
      <a
        href="{{ $modulo->ModRuta && Route::has($modulo->ModRuta) ? route($modulo->ModRuta) : '#' }}"
        class="nav-link module">
        <i class="nav-icon fas {{ $modulo->ModIcono  }}"></i>
        <p class="fw-medium">{{ $modulo->ModNom }}</p>
        @if($modulo->submodulos->count())
        <i class="right fas fa-angle-left"></i>
        @endif
      </a>
      <!-- Submodulos -->
      @if($modulo->submodulos->count())
      <ul class="nav nav-treeview">
        @foreach($modulo->submodulos as $submodulo)
        <!-- Submodulos permiso de visualizacion -->
        @if(! $submodulo->SubModPermiso || auth()->user()->can($submodulo->ModPermiso))
        <li class="nav-item">
          <a
            class="nav-link submodule"
            data-active-match="{{ $submodulo->ModRuta === 'mapaProcesos.index' ? 'exact' : 'prefix' }}"
            href="{{ $submodulo->ModRuta && Route::has($submodulo->ModRuta) ? route($submodulo->ModRuta) : '#' }}">
            <i class="nav-icon fas {{ $submodulo->ModIcono }} "></i>
            <p>{{ Str::title($submodulo->ModNom) }}</p>
          </a>
        </li>
        @endif
        @endforeach
      </ul>
      @endif
    </li>
    @endif @endforeach

    <li class="nav-item has-treeview">
      <a href="#" class="nav-link">
        <i class="nav-icon fas fa-cloud"></i>
        <p>Sesión</p>
        <p><i class="right fas fa-angle-left"></i></p>
      </a>
      <ul class="nav nav-treeview">
        <li class="nav-item">
          <a
            href="#"
            onclick="document.getElementById('logoutForm').submit();"
            class="nav-link logout-link bg-danger">
            <i class="fas fa-sign-out-alt"></i>
            Cerrar sesión
          </a>
          <form
            id="logoutForm"
            action="{{ route('logout') }}"
            method="POST"
            style="display: none">
            @csrf
          </form>
        </li>
      </ul>
    </li>
  </ul>
</nav>
