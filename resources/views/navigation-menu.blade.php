<div class="relative bg-white">
    <!-- Contenido del encabezado -->
    <header class="mx-auto px-4 py-1 flex items-center shadow-lg" style="background-color: rgba(255, 255, 255, 0.9);">
        <img src="{{ asset('images/LogoIvess-2.gif') }}" alt="Logo Ivess" class="mr-2 border-r-2 border-indigo-600 pr-4 -mt-3">

        <div class="ml-2">
            <a href="#" onclick="location.reload(); return false;" class="no-underline">
                <h1 id="animated-title" class="text-3xl font-semibold text-indigo-600 pl-16"></h1>
            </a>
            
            <a href="{{ route('cargar-archivo') }}" class="ml-4 text-indigo-600 pl-12 underline">Alta Proveedores</a>
            <a href="{{ route('cobranzas') }}" class="ml-4 text-indigo-600 underline">Cobranzas</a>
        </div>
    </header>
</div>