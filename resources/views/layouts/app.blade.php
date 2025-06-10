<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>

        <script>
            // Dark Mode Toggle
            document.addEventListener('DOMContentLoaded', () => {
                darkModeEnable()
            });

        </script>

        <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">

        <!-- CAPCHA js de paquete: anhskohbo/no-captcha-->
        {!! NoCaptcha::renderJs() !!}
        
        <!-- Iconos -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        
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
    <body style="margin: 0; padding: 0;">

        <section class="content-header mb-4 p-2 d-flex justify-content-between align-items-center sticky-top" style="min-height:60px; background-color:#0E2146; top:0; left:0; width:100%; z-index:1000;">
            <a href="{{ route('index') }}">
            <img 
            src="https://autogestion.copetran.com.co/cdn/img/logos/logo-blango.png" 
            alt="Logo" 
            style="height: 45px; object-fit: contain; padding-left: 50px;" 
            />
            </a>
        </section>
        <div style="height:60px;"></div>

        <section>
            @yield('content')
        </section>

        <script>
            // Mostrar el cuerpo del documento después de cargar todo
            window.addEventListener('load', function () {
                document.body.classList.add('show');
            }); 
        </script>

        @stack('script')

        <!-- Renderizado de componentes-->
        <x-alert /> 
        <x-modal />
        <x-toast />
        <x-loader />
    </body>
</html>
