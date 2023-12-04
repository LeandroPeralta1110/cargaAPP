<div>
    <div class="grid grid-cols-3 gap-5">
        <!-- Primer contenedor -->
        <div class="flex-1 fondocolor rounded-lg shadow-lg mx-5">
            <form wire:submit.prevent="cargarArchivo">
                <div class="bg-gradient px-6 py-3 rounded-md">
                    <h2 class="text-lg font-semibold">Archivo: </h2>
                </div>
                <input class="text-white p-4" type="file" wire:model="archivo">
                <button type="submit"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 m-4 rounded-md mt-4">Cargar Archivo
                </button>
            </form>
        </div>

     <!-- Segundo contenedor mejorado -->
     <div class="flex-1 fondocolor rounded-lg shadow-lg mx-5 overflow-hidden">
        <div class="flex justify-between bg-gradient text-white px-6 py-3 rounded-md">
            <h2 class="text-lg font-semibold">Datos duplicados</h2>
            @if($datosDuplicados)
                <button wire:click="eliminarDuplicados" class="bg-red-500 text-white px-4 py-2 rounded-md">Eliminar duplicados</button>
            @endif
        </div>
        <div class="max-h-400px overflow-y-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-3 text-left">Número de Operación</th>
                        <th class="border p-3 text-left">Datos Duplicados</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($datosDuplicados as $numeroOperacion => $duplicados)
                        <tr class="{{ $loop->odd ? 'bg-gray-50' : 'bg-white' }}">
                            <td class="border whitespace-no-wrap">{{ $numeroOperacion }}</td>
                            <td class="border" colspan="3">
                                <table class="min-w-full">
                                    <tr>
                                        <th class="border text-left whitespace-no-wrap">Impacta</th>
                                        <th class="border text-left whitespace-no-wrap">Cliente</th>
                                        <th class="border text-left whitespace-no-wrap">Importe</th>
                                    </tr>
                                    @foreach($duplicados as $duplicado)
                                        <tr>
                                            <td class="border whitespace-no-wrap">{{ $duplicado['Impacta'] }}</td>
                                            <td class="border whitespace-no-wrap">{{ $duplicado['Cliente'] }}</td>
                                            <td class="border whitespace-no-wrap">{{ $duplicado['Importe'] }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
        <!-- Tercer contenedor -->
        <div class="flex-1 fondocolor rounded-lg shadow-lg mx-5">
            <div class="bg-gradient px-6 py-3 rounded-md">
                <h2 class="text-lg font-semibold">Clientes no encontrados</h2>
            </div>
        </div>
    </div>

    <!-- Cuarto contenedor debajo de los primeros tres -->
    <!-- Cuarto contenedor debajo de los primeros tres -->
<div class="flex-1 fondocolor rounded-lg shadow-lg mx-5 mt-5 overflow-hidden">
    <div class="bg-gradient text-white px-6 py-3">
        <h2 class="text-lg font-semibold">Contenido del Archivo</h2>
    </div>
    <div class="max-h-400px overflow-y-auto">
        <table class="min-w-full table-auto">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border p-3 text-left">Impacta</th>
                    <th class="border p-3 text-left">Cliente</th>
                    <th class="border p-3 text-left">Subscripcion</th>
                    <th class="border p-3 text-left">Operacion</th>
                    <th class="border p-3 text-left">Importe</th>
                    <th class="border p-3 text-left">Pago</th>
                </tr>
            </thead>
            <tbody>
                @foreach($contenidoArchivo as $linea)
                    {{-- Verificar si la operación está en datos duplicados --}}
                    @if(isset($datosDuplicados[$linea['Operacion']]))
                        <tr class="bg-red-200">
                    @else
                        <tr class="{{ $loop->odd ? 'bg-gray-50' : 'bg-white' }}">
                    @endif
                        <td class="border p-3">{{ \Carbon\Carbon::parse($linea['Impacta'])->format('d/m/Y') }}</td>
                        <td class="border p-3">{{ $linea['Cliente'] }}</td>
                        <td class="border p-3">{{ $linea['Subscripcion'] }}</td>
                        <td class="border p-3">{{ $linea['Operacion'] }}</td>
                        <td class="border p-3">{{ $linea['Importe'] }}</td>
                        <td class="border p-3">{{ \Carbon\Carbon::parse($linea['Pago'])->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

    <div x-data="{ sidebarOpen: false }">
        <!-- Botón de la barra lateral -->
        <button @click="sidebarOpen = !sidebarOpen" class="fixed top-1/2 right-0 transform -translate-y-1/2 m-4 p-2 bg-blue-500 text-white rounded-full">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>

        <!-- Contenido principal -->
        <div class="grid grid-cols-3 gap-5">
            <!-- Resto de tu contenido -->

            <!-- Cuarto contenedor debajo de los primeros tres -->
            <div x-show="sidebarOpen" @click.away="sidebarOpen = false" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-gray-800 text-white w-64 p-4">
                <!-- Contenido de la barra lateral -->
                <h2 class="text-lg font-semibold mb-4">Opciones</h2>
                <!-- Enlace hacia client.index -->
                <a href="{{ route('client.index') }}" class="text-blue-300 hover:underline mb-4 block">Ver Clientes</a>
                <!-- Puedes agregar más botones u opciones aquí -->
                <button @click="sidebarOpen = false" class="bg-red-500 text-white px-4 py-2 rounded-md">Cerrar</button>
            </div>
        </div>
    </div>
</div>
