<nav class="mt-0 pt-0">
   <ul class="nav nav-pills nav-sidebar nav-collapse-hide-child flex-column" data-widget="treeview" role="menu">
       @foreach($modulos as $modulo)
           @if(! $modulo->ModPermiso || auth()->user()->can($modulo->ModPermiso))
           <li class="nav-item has-treeview">
               <a href="{{ $modulo->ModRuta && Route::has($modulo->ModRuta) ? route($modulo->ModRuta) : '#' }}"
                  class="nav-link module">
                   <i class="nav-icon fas {{ $modulo->ModIcono }}"></i>
                   {{ $modulo->ModNom }}
                   @if($modulo->submodulos->count())
                       <p><i class="right fas fa-angle-left"></i></p>
                   @endif
               </a>

               @if($modulo->submodulos->count())
               <ul class="nav nav-treeview">
                   @foreach($modulo->submodulos as $submodulo)
                       @if(! $submodulo->ModPermiso || auth()->user()->can($submodulo->ModPermiso))
                       <li class="nav-item">
                           <a class="nav-link submodule" href="{{ $submodulo->ModRuta && Route::has($submodulo->ModRuta) ? route($submodulo->ModRuta) : '#' }}">
                               <i class="nav-icon fas {{ $submodulo->ModIcono }}"></i>
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
           <a href="#" class="nav-link">
               <i class="nav-icon fas fa-cloud"></i>
               <p>Sesión</p>
               <p><i class="right fas fa-angle-left"></i></p>
           </a>
           <ul class="nav nav-treeview">
               <li class="nav-item">
                   <a href="#" onclick="document.getElementById('logoutForm').submit();" class="nav-link logout-link">
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
