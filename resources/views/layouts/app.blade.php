<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
   <meta charset="utf-8" />
   <meta name="viewport" content="width=device-width, initial-scale=1" />

   <title>@yield('title')</title>
   <link rel="preload" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" as="style"
      onload="this.onload=null;this.rel='stylesheet'" />
   <noscript>
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css" />
   </noscript>

   <!-- <script>
      // Dark Mode Toggle
      document.addEventListener("DOMContentLoaded", () => {
         themeManager.initTheme();
         // Configurar listener para cambios del sistema
         themeManager.setupSystemThemeListener();
         // Configurar botón toggle si existe
         const toggleButton = document.querySelector("[data-theme-toggle]");
         if (toggleButton) {
            toggleButton.addEventListener("click", () => {
               themeManager.toggleTheme();
            });
         }
      });
   </script>
 -->
   <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png" />

   <!-- CAPCHA js de paquete: anhskohbo/no-captcha-->
   {!! NoCaptcha::renderJs() !!}

   <!-- Iconos -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />

   @stack('styles')

   <style>
      body {
         visibility: hidden;
      }

      body.show {
         visibility: visible;
      }
   </style>

   <!--Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js','resources/css/custom.css','resources/css/mobile.css','resources/css/darkmode.css'])
</head>

<body class="sidebar-mini layout-fixed bodyIndex">
   <div class="wrapper">
      <!-- Pantalla de carga -->
      <div class="preloader flex-column justify-content-center align-items-center">
         <div class="spinner-border text-primary mt-2" role="status"></div>
         <p class="mt-1 fw-medium text-muted">Cargando...</p>
      </div>

      <section class="content-header mb-4 p-2 d-flex justify-content-between align-items-center sticky-top"
         style=" min-height: 60px; background-color: #0e2146; top: 0; left: 0; width: 100%; z-index: 1000; ">
         <a href="{{ route('index') }}">
            <img src="https://autogestion.copetran.com.co/cdn/img/logos/logo-blango.png" alt="Logo"
               style="height: 45px;object-fit: contain;padding-left: 50px;" />
         </a>

         {{-- Icono cambiar theme --}}
         <div style="width: 1.5em; display: flex; align-items: center; justify-content: flex-start;" class="me-3">
            <i id="toggleDarkMode" class="bi bi-sun text-light" style="cursor: pointer;"></i>
         </div>
      </section>
      <div style="height: 60px"></div>

      <section>@yield('content')</section>
   </div>

   <script>
      // Mostrar el cuerpo del documento después de cargar todo
      window.addEventListener("load", function() {
         document.body.classList.add("show");
      });

      // Función para mostrar el toast
      window.addEventListener('DOMContentLoaded', function () {
         const title = sessionStorage.getItem('toastTitle');
         const type = sessionStorage.getItem('toastType');

         if (title && type) {
               mostrarToast(title, type); 
               sessionStorage.removeItem('toastTitle');
               sessionStorage.removeItem('toastType');
         }
      });

      //dark mode toggle 
      document.addEventListener('DOMContentLoaded', () => {
         darkModeEnable()
      });    

   </script>

   <script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js" defer></script>
   @stack('script')

   <!-- Renderizado de componentes-->
   <x-alert />
   <x-modal />
   <x-toast />
   <x-loader />
</body>

</html>
