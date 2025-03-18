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

        <section>
            @yield('content')
        </section>
        
        @yield('script')

        <!-- Alertas de swal (components/alert)-->
        <x-alert /> 
    </body>
</html>
