<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>@yield('title')</title>
        
        <!-- Iconos -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

        <!--Vite -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body style="margin: 0; padding: 0;">

        <section class="content-header mb-4 p-2 d-flex justify-content-between align-items-center" style="min-height:60px; background-color:#0E2146">
            <img 
                src="https://autogestion.copetran.com.co/cdn/img/logos/logo-blango.png" 
                alt="Logo" 
                style="height: 45px; object-fit: contain; padding-left: 50px;" 
            />
        </section>

        <section>
            @yield('content')
        </section>

        @include('footer')
        
        @stack('script')

        <!-- Alertas de swal (components/alert)-->
        <x-alert /> 
        <x-loader />
    </body>
</html>
