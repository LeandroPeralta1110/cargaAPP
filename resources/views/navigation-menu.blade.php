<div class="sticky top-0 relative bg-white">
    <!-- Contenido del encabezado -->
    <header class="mx-auto px-4 py-1 flex items-center shadow-lg" style="background-color: rgba(255, 255, 255, 0.9);">
        <img src="{{ asset('images/LogoIvess-2.gif') }}" alt="Logo Ivess" class="mr-2 border-r-2 border-indigo-600 pr-4 -mt-3">

        <div class="ml-2">
            <a href="#" onclick="location.reload(); return false;" class="no-underline">
                <h1 id="animated-title" class="text-3xl font-semibold text-indigo-600 pl-16"></h1>
            </a>
            
            <!-- Enlaces de Cargar Archivo y Cobranzas -->
            <div class="flex">
                <div class="relative mt-4" x-data="{ open: false }">
                    <button @click="open = !open" class="text-sm text-gray-700 focus:outline-none">
                        <span class="mr-1">Proveedores</span>
                        <svg x-bind:class="{ 'transform rotate-180': open }" class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M10 12l-6-6h12z"/></svg>
                    </button>
                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-md shadow-lg">
                        <a class="rounded-t bg-gray-200 hover:bg-gray-400 py-2 px-4 block whitespace-no-wrap" href="{{ route('cargar-archivo') }}">Archivos (Banco Nacion)</a>
                        <a class="bg-gray-200 hover:bg-gray-400 py-2 px-4 block whitespace-no-wrap" href="{{ route('archivo-frances') }}">Archivos (Banco Francés)</a>
                    </div>
                </div>
                
                <!-- Enlace a Cobranzas -->
                <x-nav-link href="{{ route('cobranzas') }}" :active="request()->routeIs('cobranzas')">Cobranzas</x-nav-link>
            </div>
        </div>
    </header>
</div>
