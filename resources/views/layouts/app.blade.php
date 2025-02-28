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
       
    </body>
    </head>
    <body>
        @yield('content')


         <!-- jQuery -->
         <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script> 
        @yield('script')
</html>
