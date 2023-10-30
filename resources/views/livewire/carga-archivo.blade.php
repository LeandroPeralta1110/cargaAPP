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
    <!-- Aside o barra izquierda -->
    <!-- Aquí va el contenido principal -->

    @if($registrosArchivos)
    <div class="mr-3 w-2/12 p-4 bg-gray-200 rounded-2xl" style="position: absolute; right: 0; top: 48%; height: 600px;">
        <div class="bg-gray-200 px-4 py-3 rounded-md">
            <h2 class="text-lg font-semibold">Archivos Registrados:</h2>
        </div>
        <div class="overflow-y-auto" style="height: calc(100% - 50px);"> <!-- 50px de alto para el encabezado -->
            @foreach($registrosArchivos as $registro)
                <h3>
                    Archivo: {{ $registro['nombre_archivo'] }}
                    <br>
                    Tipo: {{$registro['tipo_registro']}}
                    <br>
                    Datos encontrados: {{ count($registro['datos']) }}
                    <br>
                </h3>  
                <hr class="my-4 border-t-2 border-blue-500">
                @endforeach
            </div>
        </div>
        @endif 
        
        @if (!empty($popupMessage) && ($datosFaltantesTipo1 || $datosFaltantesTipo2 || $datosNoEncontradosAltaProveedor))
        <div class="popup-container">
            <div class="alert alert-danger popup">
                <button class="close-popup-button" wire:click="closePopup">Cerrar</button>
                <h4>Datos no encontrados:</h4>
                <ul>
                    @if ($seccionSeleccionada === 'registro_tipo_1' && $datosFaltantesTipo1)
                        <li>Tipo 1:</li>
                        <ul>
                            @foreach ($datosFaltantesTipo1 as $linea => $camposFaltantes)
                                <li>Línea {{ $linea }}:
                                    @foreach ($camposFaltantes as $campoFaltante)
                                        {{ $campoFaltante }},
                                    @endforeach
                                </li>
                            @endforeach
                        </ul>
                    @endif
    
                    @if ($seccionSeleccionada === 'registro_tipo_2' && $datosFaltantesTipo2)
                        <li>Tipo 2:</li>
                        <ul>
                            @foreach ($datosFaltantesTipo2 as $linea => $camposFaltantes)
                                <li>Línea {{ $linea }}:
                                    @foreach ($camposFaltantes as $campoFaltante)
                                        {{ $campoFaltante }},
                                    @endforeach
                                </li>
                            @endforeach
                        </ul>
                    @endif
    
                    @if ($seccionSeleccionada === 'alta_proveedor' && $datosNoEncontradosAltaProveedor)
                        <li>Alta Proveedores:</li>
                        <ul>
                            @foreach ($datosNoEncontradosAltaProveedor as $linea => $camposFaltantes)
                                <li>Línea {{ $linea }}:
                                    @foreach ($camposFaltantes as $campoFaltante)
                                        {{ $campoFaltante }},
                                    @endforeach
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </ul>
            </div>
        </div>
    @endif
    
    
        @if (!empty($mensajeError))
        @if ($mostrarMensajeError)
        <div class="popup-container">
            <div class="alert alert-danger popup">
                <button class="close-popup-button" wire:click="closePopup">Cerrar</button>
                <h4>Datos no encontrados:</h4>
                <ul>
                    @if($mostrarMensajeErrorTipo1)
                        @foreach ($mostrarDatosFaltantesTipo1 as $linea => $datosFaltantes)
                            <li>Línea {{ $linea }}:
                                @foreach ($datosFaltantes as $datoFaltante)
                                    {{ $datoFaltante }},
                                @endforeach
                            </li>
                        @endforeach
                    @endif
                    @if($mostrarMensajeErrorTipo2)
                        @foreach ($datosFaltantesTipo2 as $linea => $datosFaltantes)
                            <li>Línea {{ $linea }}:
                                @foreach ($datosFaltantes as $datoFaltante)
                                    {{ $datoFaltante }},
                                @endforeach
                            </li>
                        @endforeach
                    @endif
                    @if($mostrarMensajeErrorAltaProveedores)
                    @foreach ($datosNoEncontradosAltaProveedor as $linea => $datosFaltantes)
                        <li>Línea {{ $linea }}:
                            @foreach ($datosFaltantes as $datoFaltante)
                                {{ $datoFaltante }},
                            @endforeach
                        </li>
                    @endforeach
                @endif
                </ul>
            </div>
        </div>
        @php
            // Restablece el intento de descarga para mostrar el mensaje nuevamente en futuros intentos
            $intentoDescarga = false;
        @endphp
    @endif
    @endif

    <div>
        @if (!empty($datosDuplicados))
            <!-- Aquí puedes diseñar el popup emergente para mostrar los datos duplicados -->
            <div class="mr-3 w-2/12 p-4 bg-gray-200 rounded-2xl" style="position: absolute; right: 0; top: 18%; height: 200px;">
                <h2>Datos Duplicados</h2>
                <ul>
                    @foreach ($datosDuplicados as $duplicado)
                        <li>Fila: {{ $duplicado['fila'] }}, Número de Comprobante: {{ $duplicado['numeroComprobante'] }}, Referencia: {{ $duplicado['referencia'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="w-full max-w-screen-lg p-6">
            
            {{-- <!-- Botón para la sección "Archivo de Pago" -->
            <div class="items-center ">
                <div class="mb-4">
                    <select id="seccion" wire:model="seccionSeleccionada"
                        class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md">
                        <option value="alta_proveedor">Archivos de Pago</option>
                        <option value="registro_tipo_1">Registro Tipo 1</option>
                        <option value="registro_tipo_2">Registro Tipo 2</option>
                        <option value="registro_tipo_3">Registro Tipo 3</option>
                    </select>
                </div>
            </div> --}}

        <!-- Contenido de la sección REGISTRO  TIPO 1-->
        @if ($seccionSeleccionada === 'registro_tipo_1')
            <!-- Coloca aquí el contenido de la sección "Registro Tipo 1" -->
            <p class="text-indigo-600">SECCION PARA REGISTROS TIPO 1</p>
            <div class="grid grid-cols-2 gap-8">
                <!-- Sección izquierda para el formulario de carga de archivos -->
                <div class="fondocolor rounded-lg shadow-lg">
                    <form wire:submit.prevent="cargaArchivoTipo1">
                        <div class="bg-gradient px-6 py-3 rounded-md">
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
                    <div class="bg-gradient px-6 py-3 rounded-md">
                        <h2 class="text-lg font-semibold">Instructivo: </h2>
                    </div>
                    <p class="text-white p-4">La aplicacion permite archivos CSV y TXT.</p>
                    <p class="text-white p-4">Este registro consigna los principales datos de la Empresa, titular del convenio de pago.</p>
                    <!-- Agrega cualquier otra información o instrucciones que necesites -->
                </div>

                <!-- Sección inferior para el contenido del archivo (Ocupa dos columnas) -->
                <div class="col-span-2 mt-4 bg-white rounded-lg shadow-lg">
                    <!-- Cabecera del contenido de los datos extraídos -->
                    <div class="bg-gradient px-6 py-3 rounded-md">
                        <h2 class="text-lg font-semibold">Datos Extraídos</h2>
                    </div>
     
                    <!-- Contenido del Archivo (Mostrado si hay contenido) -->
                    <div class="fondocolor p-6">
                        <div class="transition-all duration-500 ease-in-out">
                        @if ($mostrarDatosTipo1)
                            <p class="text-white text-sm font-medium text-gray-600">
                                {{ count($datosProcesadosTipo1) }} Datos encontrados
                            </p>

                            @php
                            $paginaActual = floor(($desde / 6) + 1);
                            $totalPaginas = ceil($total / $porPagina);
                        @endphp
                        
                        <p class="text-white text-sm font-medium text-gray-600">
                            Página {{ $paginaActual }} de {{ $totalPaginas }}
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
        @elseif ($seccionSeleccionada === 'registro_tipo_2')
            <!-- Coloca aquí el contenido de la sección "Registro Tipo 2" -->
            <p class="text-indigo-600">SECCION PARA REGISTROS TIPO 2</p>
            <div class="grid grid-cols-2 gap-8">
                <!-- Sección izquierda para el formulario de carga de archivos -->
                <div class="fondocolor rounded-lg shadow-lg">
                    <form wire:submit.prevent="cargaArchivoTipo2">
                        <div class="bg-gradient px-6 py-3 rounded-md">
                            <h2 class="text-lg font-semibold">Archivo: </h2>
                        </div>
                        <input class="text-white p-4" type="file" wire:model="archivo">
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 m-4 rounded-md mt-4">Cargar Archivo</button>
                        @if($cargando)
                            <!-- Indicador de carga (puedes personalizarlo) -->
                            <div class="loader"></div>
                        @endif
                    </form>
                </div>
                

                <!-- Sección derecha para las instrucciones -->
                <div class="fondocolor rounded-lg shadow-lg ">
                    <div class="bg-gradient px-6 py-3 rounded-md">
                        <h2 class="text-lg font-semibold">Instructivo: </h2>
                    </div>
                    <p class="text-white p-4">La aplicacion permite archivos CSV y TXT.</p>
                    <p class="text-white p-4">Este registro posee el detalle de los pagos a efectuar.</p>
                    <!-- Agrega cualquier otra información o instrucciones que necesites -->
                </div>

                <!-- Sección inferior para el contenido del archivo (Ocupa dos columnas) -->
                <div class="col-span-2 mt-4 bg-white rounded-lg shadow-lg">
                    <!-- Cabecera del contenido de los datos extraídos -->
                    <div class="bg-gradient px-6 py-3 rounded-md">
                        <h2 class="text-lg font-semibold">Datos Extraídos</h2>
                    </div>

                    <!-- Contenido del Archivo (Mostrado si hay contenido) -->
                    <div class="fondocolor p-6" wire:transition.opacity.duration.500ms.ease-in-out
                        x-data="{ isOpen: false }">
                        @if ($mostrarDatosTipo2)
                            <p class="text-white text-sm font-medium text-gray-600">
                                {{ count($datosProcesadosTipo2)}} Datos encontrados
                            </p>

                            @php
                                $paginaActual = floor(($desde / 6) + 1);
                                $totalPaginas = ceil($total / $porPagina);
                            @endphp
                            
                            <p class="text-white text-sm font-medium text-gray-600">
                                Página {{ $paginaActual }} de {{ $totalPaginas }}
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
        @elseif ($seccionSeleccionada === 'registro_tipo_3')
            <!-- Coloca aquí el contenido de la sección "Registro Tipo 3" -->
            <p class="text-indigo-600">SECCION PARA REGISTROS TIPO 3</p>
            <div class="grid grid-cols-1 gap-8">

                <!-- Sección derecha para las instrucciones -->
                <div class="fondocolor rounded-lg shadow-lg ">
                    <div class="bg-gradient px-6 py-3 rounded-md">
                        <h2 class="text-lg font-semibold">Instructivo: </h2>
                    </div>
                    <p class="text-white p-4">La aplicacion permite archivos CSV y TXT.</p>
                    <p class="text-white p-4">Este registro contiene el resumen de la cantidad de pagos a efectuar y el monto total abonado.</p>
                    <!-- Agrega cualquier otra información o instrucciones que necesites -->
                </div>

                <!-- Sección inferior para el contenido del archivo (Ocupa dos columnas) -->
                <div class="col-span-2 mt-4 bg-white rounded-lg shadow-lg">
                    <!-- Cabecera del contenido de los datos extraídos -->
                    <div class="bg-gradient px-6 py-3 rounded-md">
                        <h2 class="text-lg font-semibold">Datos Extraídos</h2>
                    </div>

                    <!-- Contenido del Archivo (Mostrado si hay contenido) -->
                    <div class="fondocolor p-6" wire:transition.opacity.duration.500ms.ease-in-out
                        x-data="{ isOpen: false }">
                        @if ($mostrarDatosTipo3)
                            <p class="text-white text-sm font-medium text-gray-600">
                                {{ count($datosProcesadosTipo3) }} Datos encontrados
                            </p>

                            @php
                                $paginaActual = floor(($desde / 6) + 1);
                                $totalPaginas = ceil($total / $porPagina);
                            @endphp
                            
                            <p class="text-white text-sm font-medium text-gray-600">
                                Página {{ $paginaActual }} de {{ $totalPaginas }}
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
        @endif

        @if ($seccionSeleccionada === 'alta_proveedor')
            <section>
                <h3 class="text-indigo-600">SECCION DE ALTA A PROVEEDORES</h3>
                <div class="grid grid-cols-2 gap-8">
                    <!-- Sección izquierda para el formulario de carga de archivos -->
                    <div class="fondocolor rounded-lg shadow-lg">
                        <form wire:submit.prevent="procesarArchivosAltaProveedores">
                            <div class="bg-gradient px-6 py-3 rounded-md">
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
                        <div class="bg-gradient px-6 py-3 rounded-md">
                            <h2 class="text-lg font-semibold">Instructivo: </h2>
                        </div>
                        <p class="text-white p-4">La aplicacion permite archivos CSV y TXT.</p>
                        <p class="text-white p-4">Esta seccion registra cuentas de credito.</p>
                        <!-- Agrega cualquier otra información o instrucciones que necesites -->
                    </div>

                    <!-- Sección inferior para el contenido del archivo (Ocupa dos columnas) -->
                    <div class="col-span-2 mt-4 bg-white rounded-lg shadow-lg">
                        <!-- Cabecera del contenido de los datos extraídos -->
                        <div class="bg-gradient px-6 py-3 rounded-md">
                            <h2 class="text-lg font-semibold">Datos Extraídos</h2>
                        </div>

                        <!-- Contenido del Archivo (Mostrado si hay contenido) -->
                        <div class="fondocolor p-6">
                                <!-- Animación de carga -->
                                @if ($mostrarDatosAltaProveedor)
                                <p class="text-white text-sm font-medium text-gray-600">
                                    {{ count($datosAltaProveedor) }} Datos encontrados
                                </p>
                                @php
                                $paginaActual = floor(($desde / 6) + 1);
                                $totalPaginas = ceil($total / $porPagina);
                                @endphp
                                
                                <p class="text-white text-sm font-medium text-gray-600">
                                    Página {{ $paginaActual }} de {{ $totalPaginas }}
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
                                        <form wire:submit.prevent="eliminarUltimosDatos('{{ $identificadorUnico }}')">
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
            </section>
        @endif
    </div>
</div>