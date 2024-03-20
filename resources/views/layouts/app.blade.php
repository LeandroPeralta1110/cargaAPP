<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Vortex</title>

        <link rel="icon" href="{{ asset('./images/agualogo.jpg') }}" type="image/x-icon">
        <link href="{{ asset('build/assets/app.css') }}" rel="stylesheet">
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        <style>
            /* Estilo para personalizar el icono de carga */
            .cargando-icono {
                display: inline-block;
                width: 20px;
                height: 20px;
                border: 4px solid #3490dc; /* Color azul, puedes ajustarlo según tu paleta de colores */
                border-top: 4px solid transparent;
                border-radius: 50%;
                animation: spin 1s linear infinite; /* Animación de rotación */
            }
    
            /* Animación de rotación */
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <x-banner />
        <div class=" bg-gray-100">
            @livewire('navigation-menu')
            
            <!-- Page Content -->
        <div class="min-h-screen bg-gray-100  bg-cover bg-center bg-fixed imagenfondo">
            <main >
                {{ $slot }}
            </main>
            </div>
        </div>
        
        <div id="footer" class="hidden bg-gray-200 text-center py-2">
            Área de Sistemas Ivess-ElJumillano &copy; {{ date('Y') }}
        </div>

        @stack('modals')

        @livewireScripts
        <script src="{{ asset('build/assets/app.js') }}"></script>
        <script>
            // Texto original que quieres animar
            const textoOriginal = "Vortex-Data (Procesador de Archivos)";
            
            // Elemento del título
            const titulo = document.getElementById("animated-title");
            
            let indice = 0;
            
            const intervalo = setInterval(function () {
                // Agrega la siguiente letra al título
                titulo.textContent = textoOriginal.slice(0, indice);
                indice++;
            
                // Detén la animación cuando hayas mostrado todo el texto
                if (indice > textoOriginal.length) {
                    clearInterval(intervalo);
                }
            }, 100); // Ajusta el intervalo según la velocidad deseada

            // JavaScript para mostrar el pie de página cuando se desplaza el contenido
            window.addEventListener('scroll', function() {
                var footer = document.getElementById('footer');
                if (footer) {
                    if (window.scrollY + window.innerHeight >= footer.offsetTop) {
                        footer.style.display = 'block';
                    } else {
                        footer.style.display = 'none';
                    }
                }
            });
        </script>
    </body>
</html>
