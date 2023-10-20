<div>
        <div class="flex bg-cover bg-center bg-fixed">
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

            <livewire:seccion-archivos-registrados/>
            @livewire('seccion-archivos-registrados')

            <div class="w-full max-w-screen-lg p-6">
        <h3 class="text-white">SECCION DE ALTA A PROVEEDORES</h3>
        <div class="grid grid-cols-2 gap-8">
            <!-- Sección izquierda para el formulario de carga de archivos -->
            <div class="fondocolor rounded-lg shadow-lg">
                <form wire:submit.prevent="procesarArchivosAltaProveedores">
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
                <p class="text-white p-4">Esta seccion registra cuentas de credito.</p>
                <!-- Agrega cualquier otra información o instrucciones que necesites -->
            </div>

            <!-- Sección inferior para el contenido del archivo (Ocupa dos columnas) -->
            <div class="col-span-2 mt-4 bg-white rounded-lg shadow-lg">
                <!-- Cabecera del contenido de los datos extraídos -->
                <div class="bg-gray-200 px-6 py-3 rounded-md">
                    <h2 class="text-lg font-semibold">Datos Extraídos</h2>
                </div>

                <!-- Contenido del Archivo (Mostrado si hay contenido) -->
                <div class="fondocolor p-6">
                        <!-- Animación de carga -->
                        @if ($mostrarDatosAltaProveedor)
                        <p class="text-white text-sm font-medium text-gray-600">
                            {{ count($datosAltaProveedor) }} Datos encontrados
                        </p>
                        <div class="flex">
                            <div class="p-2 flex items-center">
                                <button>
                                    <a wire:click="descargarDatosAltaProveedores"
                                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">Descargar
                                        Datos</a>
                                </button>
                            </div>
                            <div class="p-2">
                                <form wire:submit.prevent="eliminarUltimosDatos">
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
                                        <th class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider relative group">
                                            CBU
                                            <div class="popup-mensaje absolute hidden -top-8 left-1/2 transform -translate-x-1/2 bg-gray-700 text-white px-2 py-1 text-center rounded text-xs opacity-0 transition-opacity duration-300 group-hover:opacity-100 group-hover:block">
                                                Mensaje de error
                                            </div>
                                        </th>
                                        <th
                                            class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                            Alias
                                        </th>
                                        <th
                                            class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                            ID Tipo
                                        </th>
                                        <th
                                            class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                            CUIT/CUIL/CDI
                                        </th>
                                        <th
                                            class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                            Tipo de Cuenta
                                        </th>
                                        <th
                                            class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                            Referencia de Cuenta
                                        </th>
                                        <th
                                            class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th
                                            class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                            Titulares
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    @foreach ($datosAltaProveedor as $index => $fila)
                                        @if ($index >= $desde && $index < $hasta)
                                            <tr>
                                                <td
                                                    class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                     <?php echo isset($fila['cbu']) ? $fila['cbu'] : ''; ?>
                                                </td>
                                                <td
                                                    class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                     <?php echo isset($fila['alias']) ? $fila['alias'] : ''; ?>
                                                </td>
                                                <td
                                                    class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                    <?php echo isset($fila['id_tipo']) ? $fila['id_tipo'] : ''; ?>
                                                </td>
                                                <td
                                                    class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                     <?php echo isset($fila['cuit']) ? $fila['cuit'] : ''; ?>
                                                </td>
                                                <td
                                                    class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                    <?php echo isset($fila['tipo_cuenta']) ? $fila['tipo_cuenta'] : ''; ?>
                                                </td>
                                                <td
                                                    class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                     <?php echo isset($fila['referencia']) ? $fila['referencia'] : ''; ?>
                                                </td>
                                                <td
                                                    class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                    <?php echo isset($fila['email']) ? $fila['email'] : ''; ?>
                                                </td>
                                                <td
                                                    class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                    <?php echo isset($fila['titulares']) ? $fila['titulares'] : ''; ?>
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