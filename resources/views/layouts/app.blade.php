<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>
        
        <!-- Iconos -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <!--Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js','resources/css/custom.css','resources/css/mobile.css'])
    </head>
    <body style="margin: 0; padding: 0;">

        <section class="content-header mb-4 p-2 d-flex justify-content-between align-items-center" style="min-height:60px; background-color:#0E2146">
            <a href="{{ route('login') }}">
                <img 
                    src="https://autogestion.copetran.com.co/cdn/img/logos/logo-blango.png" 
                    alt="Logo" 
                    style="height: 45px; object-fit: contain; padding-left: 50px;" 
                />
            </a>
        </section>

        <section>
            @yield('content')
        </section>

        @include('footer')
        
        @stack('script')

        <!-- Renderizado de componentes-->
        <x-alert /> 
        <x-modal />
        <x-toast />
        <x-loader />
    </body>
</html>
