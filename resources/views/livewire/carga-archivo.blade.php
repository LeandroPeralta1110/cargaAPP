<div class="flex bg-cover bg-center bg-fixed">
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

        @if ($seccionSeleccionada === 'alta_proveedor')
             <livewire:alta-proveedores/>
        @elseif ($seccionSeleccionada === 'registro_tipo_2')
             <livewire:registros-tipo2/>
        @elseif ($seccionSeleccionada === 'registro_tipo_3')
             <livewire:registros-tipo3/>
        @elseif ($seccionSeleccionada === 'registro_tipo_1')
            <livewire:registros-tipo1/>
        @endif
</div>