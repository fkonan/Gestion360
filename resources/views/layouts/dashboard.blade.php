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
    <body class="m-0 p-0">
        <div>
            @auth
                @include('layouts.menu')
            @endauth

            <div class="flex-grow-1 pt-4" style="margin-left: 250px; padding: 1rem; width: calc(100% - 250px);">
                <section>
                    @yield('content')
                </section>
            </div>
        </div>

        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
        @yield('script')
    </body>
</html>
