<div>
    <livewire:side-bar-component/>
    <!-- Contenido de la sección REGISTRO  TIPO 1-->
    <!-- Coloca aquí el contenido de la sección "Registro Tipo 1" -->
    <p class="text-white">SECCION PARA REGISTROS TIPO 1</p>
    <div class="grid grid-cols-2 gap-8">
        <!-- Sección izquierda para el formulario de carga de archivos -->
        <div class="fondocolor rounded-lg shadow-lg">
            <form wire:submit.prevent="cargaArchivoTipo1">
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
            <p class="text-white p-4">Este registro consigna los principales datos de la Empresa, titular del convenio de pago.</p>
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
                <div class="transition-all duration-500 ease-in-out">
                @if ($mostrarDatosTipo1)
                    <p class="text-white text-sm font-medium text-gray-600">
                        {{ count($datosProcesadosTipo1) }} Datos encontrados
                    </p>
                    <div class="flex">
                        <div class="p-2 flex items-center">
                            <button>
                                <a wire:click="descargarDatosRegistroTipo1"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">Descargar Datos</a>
                            </button>
                        </div>
                        <div class="p-2">
                            <form wire:submit.prevent="eliminarUltimoArchivoTipo1">
                                <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md">
                                    Volver
                                </button>
                            </form>
                        </div>
                    </div>
                    <div style="max-height: 400px; overflow-y: auto;">
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th style="height: 20px;"
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider" >
                                        REGISTRO
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        CUIT EMPRESA
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        CUENTA SUC.
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        CBU
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        MONEDA
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        FECHA DE PAGO
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        INFO CRITERIO EMPRESA
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        TIPO PAGO
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        CLASE PAGOS
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        CODIGO CONVENIO
                                    </th>
                                    <th
                                        class="px-2 py-3 bg-gray-300 text-left text-xs leading-4 font-medium text-gray-700 uppercase tracking-wider">
                                        NUMERO DE ENVIO
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white">
                                @foreach ($datosProcesadosTipo1 as $index => $fila)
                                    @if ($index >= $desde && $index < $hasta)
                                        <tr>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                {{ $fila['tipo_registro'] }}
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['cuit']) ? $fila['cuit'] : ''; ?>
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['entidad_acreditar']) ? $fila['entidad_acreditar'] : ''; ?>
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['cbu']) ? $fila['cbu'] : ''; ?>
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['moneda']) ? $fila['moneda'] : ''; ?>
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['fecha_pago']) ? $fila['fecha_pago'] : ''; ?>
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['info_criterio_empresa']) ? $fila['info_criterio_empresa'] : ''; ?>
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['tipo_pagos']) ? $fila['tipo_pagos'] : ''; ?>
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['clase_pagos']) ? $fila['clase_pagos'] : ''; ?>
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['codigo_convenio']) ? $fila['codigo_convenio'] : ''; ?>
                                            </td>
                                            <td class="px-2 py-4 whitespace-no-wrap border-b border-gray-200">
                                                <?php echo isset($fila['numero_envio']) ? $fila['numero_envio'] : ''; ?>
                                            </td>
                                        </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <h1>Hola mundo</h1>
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
            </div>
        </div>
    </div>
</div>
