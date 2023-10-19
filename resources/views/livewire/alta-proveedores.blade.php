<div>
    <livewire:side-bar-component/>
    <!-- Coloca aquí el contenido de la sección "Registro Tipo 3" -->
    <p class="text-white">SECCION PARA REGISTROS TIPO 3</p>
    <div class="grid grid-cols-1 gap-8">

        <!-- Sección derecha para las instrucciones -->
        <div class="fondocolor rounded-lg shadow-lg ">
            <div class="bg-gray-200 px-6 py-3 rounded-md">
                <h2 class="text-lg font-semibold">Instructivo: </h2>
            </div>
            <p class="text-white p-4">La aplicacion permite archivos CSV y TXT.</p>
            <p class="text-white p-4">Este registro contiene el resumen de la cantidad de pagos a efectuar y el monto total abonado.</p>
            <!-- Agrega cualquier otra información o instrucciones que necesites -->
        </div>

        <!-- Sección inferior para el contenido del archivo (Ocupa dos columnas) -->
        <div class="col-span-2 mt-4 bg-white rounded-lg shadow-lg">
            <!-- Cabecera del contenido de los datos extraídos -->
            <div class="bg-gray-200 px-6 py-3 rounded-md">
                <h2 class="text-lg font-semibold">Datos Extraídos</h2>
            </div>

            <!-- Contenido del Archivo (Mostrado si hay contenido) -->
            <div class="fondocolor p-6" wire:transition.opacity.duration.500ms.ease-in-out
                x-data="{ isOpen: false }">
                @if ($mostrarDatosTipo3)
                    <p class="text-white text-sm font-medium text-gray-600">
                        {{ count($datosProcesadosTipo3) }} Datos encontrados
                    </p>
                    <div class="p-2">
                        <button>
                            <a wire:click="descargarDatosRegistroTipo3"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">Descargar
                                Datos</a>
                        </button>
                    </div>
                    <div class="overflow-y-auto max-h-[400px]" >
                        <table class="min-w-full overflow-y-auto max-h-[1200px]" wire:key="miTabla">
                            <thead>
                                <tr>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        REGISTRO
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        TOTAL IMPORTE
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        TOTAL CANT. REGISTROS TIPO 2
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        IMPORTE ACEPTADOS
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        CANTIDAD DE REGISTROS TIPO 2 ACEPTADOS
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        IMPORTE RECHAZADOS
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        CANTIDAD DE REGISTROS TIPO 2 RECHAZADOS
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        IMPORTE COMISIONES
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        IMPORTE IVA
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        IMPORTE RETENCION IVA
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        IMPORTE PERCEPCION INGRESOS BRUTOS
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        IMPORTE SELLADO PROVINCIAL
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach ($datosProcesadosTipo3 as $index => $fila)
                                    @if ($index >= $desde && $index < $hasta)
                                        <tr>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['tipo_registro'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['total_importe'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['total_registros'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['importe_aceptados'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['cantidad_registros_tipo2_aceptados'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['importes_rechazados'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['cantidad_registros_tipo2_rechazados'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['importe_comision'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['importe_IVA'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['importe_retencion_IVA'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['importe_ingreso_bruto'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['importe_sellado_provincial'] }}
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        @if ($total > $porPagina)
                            <div class="flex justify-between">
                                @if ($pagina > 1)
                                    <button wire:click="paginaAnterior"
                                        class="text-blue-500 hover:underline cursor-pointer">&larr;
                                        Anterior</button>
                                @endif

                                @if ($total > $hasta)
                                    <button wire:click="siguientePagina"
                                        class="text-blue-500 hover:underline cursor-pointer">Siguiente
                                        &rarr;</button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
