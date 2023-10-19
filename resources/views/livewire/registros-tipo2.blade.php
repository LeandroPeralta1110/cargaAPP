<div>
    <div class="flex bg-cover bg-center bg-fixed imagenfondo">
    <aside class="w-1/7 h-screen p-6 flex flex-col bg-gradient">
        <!-- Botón para la sección "Alta Proveedores" -->
        <button wire:click="cambiarSeccion('alta_proveedor')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Alta Proveedores
        </button>
        <!-- Botón para la sección "Registros tipo 1" -->
        <button wire:click="cambiarSeccion('registro_tipo_1')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Registros tipo 1
        </button>
        <!-- Botón para la sección "Registros tipo 2" -->
        <button wire:click="cambiarSeccion('registro_tipo_2')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Registros tipo 2
        </button>
        <!-- Botón para la sección "Registros tipo 3" -->
        <button wire:click="cambiarSeccion('registro_tipo_3')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Registros tipo 3
        </button>
    </aside>
        <div class="w-full max-w-screen-lg p-6">
            <!-- Coloca aquí el contenido de la sección "Registro Tipo 2" -->
            <p class="text-white">SECCION PARA REGISTROS TIPO 2</p>
            <div class="grid grid-cols-2 gap-8">
                <!-- Sección izquierda para el formulario de carga de archivos -->
                <div class="fondocolor rounded-lg shadow-lg">
                    <form wire:submit.prevent="cargaArchivoTipo2">
                        <div class="bg-gray-200 px-6 py-3 rounded-md">
                            <h2 class="text-lg font-semibold">Archivo: </h2>
                        </div>
                        <input class="text-white p-4" type="file" wire:model="archivo">
                        <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 m-4 rounded-md mt-4">Cargar
                            Archivo</button>
                    </form>
                </div>

                <!-- Sección derecha para las instrucciones -->
                <div class="fondocolor rounded-lg shadow-lg ">
                    <div class="bg-gray-200 px-6 py-3 rounded-md">
                        <h2 class="text-lg font-semibold">Instructivo: </h2>
                    </div>
                    <p class="text-white p-4">La aplicacion permite archivos CSV y TXT.</p>
                    <p class="text-white p-4">Este registro posee el detalle de los pagos a efectuar.</p>
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
                        @if ($mostrarDatosTipo2)
                            <p class="text-white text-sm font-medium text-gray-600">
                                {{ count($datosProcesadosTipo2) }} Datos encontrados
                            </p>
                            <div class="flex">
                                <div class="p-2 flex items-center">
                                    <button>
                                        <a wire:click="descargarDatosRegistroTipo2"
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">Descargar
                                            Datos</a>
                                    </button>
                                </div>
                                <div class="p-2">
                                    <form wire:submit.prevent="eliminarUltimosDatosTipo2">
                                        <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md">
                                            Volver
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="overflow-y-auto max-h-[400px]">
                                <table class="min-w-full overflow-y-auto max-h-[1200px]">
                                    <thead>
                                        <tr>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                REGISTRO TIPO
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                ENTIDAD
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                SUCURSAL
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                CBU
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                CUIT
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                IMPORTE
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                REFERENCIA
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                IDENTIFICACION CLIENTE
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                CLASE DE DOCUMENTO
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                TIPO DE CUENTA
                                            </th>
                                            <th
                                                class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                                IDENTIFICACION PRESTAMO
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white">
                                        @foreach ($datosProcesadosTipo2 as $index => $fila)
                                            @if ($index >= $desde && $index < $hasta)
                                                <tr>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        {{ $fila['tipo_registro'] }}
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        <?php echo isset($fila['entidad_acreditar']) ? $fila['entidad_acreditar'] : ''; ?>
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        <?php echo isset($fila['sucursal_acreditar']) ? $fila['sucursal_acreditar'] : ''; ?>
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        <?php echo isset($fila['cbu']) ? $fila['cbu'] : ''; ?>
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        <?php echo isset($fila['cuit']) ? $fila['cuit'] : ''; ?>
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        <?php echo isset($fila['importe']) ? $fila['importe'] : ''; ?>
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        <?php echo isset($fila['referencia']) ? $fila['referencia'] : ''; ?>
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        <?php echo isset($fila['identificacion_cliente']) ? $fila['identificacion_cliente'] : ''; ?>
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                       <?php echo isset($fila['clase_documento']) ? $fila['clase_documento'] : ''; ?>
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        <?php echo isset($fila['tipo_documento']) ? $fila['tipo_documento'] : '';?>
                                                    </td>
                                                    <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                        <?php echo isset($fila['identificador_prestamo']) ? $fila['identificador_prestamo'] : '';?>
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
    </div>
</div>
