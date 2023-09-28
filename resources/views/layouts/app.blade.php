<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Vortex</title>

        <link rel="icon" href="{{ asset('./images/tornado-icon.jpg') }}" type="image/x-icon"">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <link rel="stylesheet" href="./css/app.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <x-banner />

        <div class="min-h-screen bg-gray-100">
            @livewire('navigation-menu')
            
            <!-- Page Content -->
            <main >
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts
        <script>
              var tablaDataTable = null;

            function inicializarDataTable() {
                if (tablaDataTable !== null) {
                    tablaDataTable.clear().destroy();
                }

                tablaDataTable = $('#miTabla').DataTable();
            }

            $(document).ready(function() {
                inicializarDataTable();
            });
        </script>
    </body>
</html>
