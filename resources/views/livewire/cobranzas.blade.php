<div>
    <div class="grid grid-cols-3 gap-5">
        <!-- Primer contenedor -->
        <div class="flex-1 fondocolor rounded-lg shadow-lg mx-5 h-64">
    <form wire:submit.prevent="cargarArchivo">
        <div class="bg-gradient px-6 py-3 text-white rounded-md">
            <h2 class="text-lg font-semibold">Archivo: </h2>
        </div>
        <input class="text-white p-4" type="file" wire:model="archivo">
        <button type="submit"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 m-4 rounded-md mt-4">
            Cargar Archivo
        </button>
    </form>
    <div class="relative">
        <span wire:loading wire:target="archivo" class="absolute right-2 bottom-2">
            <span class="cargando-icono"></span>
        </span>
        <span wire:loading wire:target="cargarArchivo" class="absolute right-2 bottom-2">
            <span class="cargando-icono"></span>
        </span>
    </div>
</div>

     <!-- Segundo contenedor mejorado -->
     <div class="flex-1 fondocolor rounded-lg shadow-lg mx-5 overflow-hidden h-64">
        <div class="flex justify-between bg-gradient text-white px-6 py-3 rounded-md">
            <h2 class="text-lg font-semibold">Cliente a buscar</h2>
        </div>
        <div class="flex items-center space-x-4 px-6 py-3">
            <!-- Input para ingresar el ID del cliente -->
            <input type="text" wire:model.defer="numeroOperacion" wire:input.debounce.400ms="actualizarTabla" class="p-1">
            <!-- Campo de fecha -->
            <input type="date" wire:model.defer="fecha" wire:input.debounce.400ms="actualizarTabla" class="p-1">
        </div>
        
        <!-- Loader -->
        <div class="relative">
            <span wire:loading wire:target="actualizarTabla" class="absolute right-2 bottom-2">
                <span class="cargando-icono"></span>
            </span>
        </div>
    
        <div class="max-h-400px overflow-y-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-3 text-left">Cliente</th>
                        <th class="border p-3 text-left">DNI/CUIL</th>
                        <th class="border p-3 text-left">Ultimo Recibo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="border p-3 bg-gray-50">{{ $clinombre }}</td>
                        <td class="border p-3 bg-gray-50">{{ $cliCuit }}</td>
                        <td class="border p-3 bg-white relative">
                            @if ($ultimaReciboCliente)
                            {{ $ultimaReciboCliente }}
                                <span class="absolute top-0 right-0 text-green-500"> <!-- Clase de color verde -->
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </span>
                            @else
                                <p>NO existe Recibo</p>
                            @endif
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if($recibosCliente)
        <div class="overflow-y-auto max-h-[300px]">
            <table class="min-w-full overflow-y-auto max-h-[300px]">
                @if(!$recibosCliente->isEmpty() && $recibosCliente != null)
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border p-3 text-left">Fecha de Emisión</th>
                            <th class="border p-3 text-left">Número de Recibo</th>
                        </tr>
                    </thead>
                @endif
                <tbody>
                    @foreach($recibosCliente as $recibo)
                        <tr>
                            @if(isset($recibo->CVE_FEMISION))
                                <td class="border p-3 bg-gray-50">{{ date('d-m-Y', strtotime($recibo->CVE_FEMISION)) }}</td>
                            @endif
                            @if(isset($recibo->IdentComp))
                                <td class="border p-3 bg-gray-50">{{ $recibo->IdentComp }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if(count($sinFactura) > 0)
    <div class="flex-1 fondocolor rounded-lg shadow-lg mx-5 overflow-hidden" style="height: auto;">
        <div class="flex justify-between bg-gradient text-white px-6 py-3 rounded-md">
            <h2 class="text-lg font-semibold">Clientes sin Recibo/factura</h2>
        </div>
    
        <div class="p-4 overflow-y-auto" style="max-height: 300px;"> <!-- Ajustar la altura máxima según sea necesario -->
            <div class="overflow-x-auto">
                <table id="tabla-sin-factura" class="min-w-full bg-white border border-gray-300 shadow-sm rounded-md">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-4 py-2">ID</th>
                            <th class="px-4 py-2">Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sinFactura as $cliente)
                            <tr wire:click="abrirPopup('{{ $cliente['ID'] }}','{{$cliente['ID_POSICION']}}')" onclick="highlightRow(this, 'tabla-sin-factura')" class="hover:bg-gray-100 cursor-pointer">
                                <td class="px-4 py-2">{{ $cliente['ID'] }}</td>
                                <td class="px-4 py-2">{{ $cliente['Nombre'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@if($mostrarPopUp)
    <div class="popup-container">
        <div class="popup relative"> <!-- Agregamos relative para que el position absolute funcione correctamente -->
            <!-- Icono de carga -->
            <span wire:loading wire:target="seleccionarFactura" class="absolute top-2 right-2">
                <span class="cargando-icono"></span>
            </span>
            <!-- Botón de cierre -->
            <button class="close-popup-button" wire:click="cerrarPopup">Cerrar</button>
            <!-- Título -->
            <h2 class="text-lg font-semibold">Facturas Libres del Cliente {{ $clienteSeleccionado }}</h2>
            <!-- Contenido de la tabla -->
            @if($facturasLibres->isEmpty())
                <p>Este cliente no tiene facturas libres.</p>
            @else
                <table id="facturas-libres" class="table-auto">
                    <thead>
                        <tr>
                            <th class="px-4 py-2">Identificador de Factura</th>
                            <th class="px-4 py-2">Fecha de Emisión</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($facturasLibres as $factura)
                            <tr onmouseover="highlightRowTable(this)" onmouseout="unhighlightRow(this)" wire:click="seleccionarFactura('{{ $factura->IdentComp }}', '{{ $clienteSeleccionado }}','{{$idPosicionCli}}')"  class="cursor-pointer {{ $factura->cve_SaldoMonCC1 > 0 ? 'factura-con-saldo-pendiente' : '' }}">
                                <td class="border px-4 py-2">{{ $factura->IdentComp }}</td>
                                <td class="border px-4 py-2">{{ \Carbon\Carbon::parse($factura->CVE_FEMISION)->format('d-m-Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@endif

    </div>

    <!-- Cuarto contenedor  -->
    <div class="flex-1 fondocolor rounded-lg shadow-lg mx-5 mt-5 overflow-hidden">
        <div class="bg-gradient text-white px-6 py-3 flex justify-between items-center">
            <h2 class="text-lg font-semibold">Contenido del Archivo</h2>
            @if($contenidoArchivo)
                <div class="flex items-center"> <!-- Contenedor de los botones -->
                    <button wire:click="reorganizarIndices" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 m-2 rounded-md mt-4">
                        <i class="fas fa-sort-numeric-down"></i> <!-- Icono de Font Awesome para reorganizar índices -->
                    </button>
                    <button wire:click="descargarNumerosExcel" class="bg-green-500 hover:bg-green-600 text-white mr-2 px-4 py-2 m-2 rounded-md mt-4">
                        Descargar Num. Recibos en Excel
                    </button>
                    <button wire:click="descargarArchivoTxt" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 m-2 rounded-md mt-4">
                        Descargar Archivo
                    </button>
                    <span wire:loading wire:target="descargarArchivoTxt" class="absolute right-2 bottom-2">
                        <span class="cargando-icono"></span>
                    </span>
                    <span wire:loading wire:target="descargarNumerosExcel" class="absolute right-2 bottom-2">
                        <span class="cargando-icono"></span>
                    </span>
                    <span wire:loading wire:target="reorganizarIndices" class="absolute right-2 bottom-2">
                        <span class="cargando-icono"></span>
                    </span>
                </div>
            @endif
        </div>                
        <div class="max-h-400px overflow-y-auto">
            <table class="min-w-full table-auto">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border p-3 text-left">ID</th>
                        <th class="border p-3 text-left">Razon Social</th>
                        <th class="border p-3 text-left">DNI/CUIL</th>
                        <th class="border p-3 text-left">Operacion</th>
                        <th class="border p-3 text-left">Impacta</th>
                        <th class="border p-3 text-left">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contenidoArchivo as $index => $linea)
                        <tr class="{{ $index % 2 === 0 ? 'bg-gray-100' : 'bg-white' }}">
                            <td class="border p-3">{{ $linea['ID'] }}</td>
                            <td class="border p-3 w-1/4">{{ $linea['RSOC'] }}</td>
                            <td class="border p-3">{{ $linea['CUIT'] }}</td>
                            <td class="border p-3">{{ $linea['OPERACIÓN'] }}</td>
                            <td class="border p-3">{{ \Carbon\Carbon::parse($linea['IMPACTA'])->format('d/m/Y') }}</td>
                            <td class="border p-3">{{ $linea['IMPORTE'] }}</td>
                            {{-- Mostrar el campo 'CUIT' obtenido de la base de datos --}}
                        </tr>
                    @endforeach
                </tbody>                
            </table>
        </div>
    </div>
    
   {{--  <div x-data="{ sidebarOpen: false }">
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
                <a href="{{ route('clients.index') }}" class="text-blue-300 hover:underline mb-4 block">Ver Clientes</a>
                <!-- Puedes agregar más botones u opciones aquí -->
                <button @click="sidebarOpen = false" class="bg-red-500 text-white px-4 py-2 rounded-md">Cerrar</button>
            </div>
        </div>
    </div> --}}
</div>
<script>
    function highlightRow(row, tableId) {
            // Quita la clase 'bg-gray-100' de todas las filas de la tabla
            document.querySelectorAll('#' + tableId + ' tbody tr').forEach(function(row) {
                row.classList.remove('bg-gray-100');
            });

            // Agrega la clase 'bg-gray-100' a la fila clicada
            row.classList.add('bg-gray-100');
        }

        function highlightRowTable(row) {
        row.classList.add('bg-gray-100');
    }

    function unhighlightRow(row) {
        row.classList.remove('bg-gray-100');
    }

    document.addEventListener('DOMContentLoaded', function () {
        Livewire.on('mostrarPopupFacturas', clienteId => {
            Livewire.emit('mostrarPopupFacturas', clienteId);
        });
    });
</script>
{{-- <script>
    document.addEventListener('livewire:load', function () {
        Livewire.on('esperarYConsultar', function (idCliente) {
            // Esperar 3 segundos (3000 milisegundos)
            setTimeout(function () {
                Livewire.emit('realizarConsulta', idCliente);
            }, 3000);
        });
    });
</script> --}}
