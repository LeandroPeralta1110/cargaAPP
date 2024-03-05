<div class="flex bg-cover bg-center bg-fixed imagenfondo">
    <aside class="w-1/7 h-screen p-6 flex flex-col bg-gradient">
        <!-- Botón para la sección "Alta Proveedores" -->
        <button wire:click="cambiarSeccion('alta_proveedor')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Registro cabecera
        </button>
        <!-- Botón para la sección "Registros tipo 1" -->
        <button wire:click="cambiarSeccion('registro_tipo_1')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Primer Registro
        </button>
        <!-- Botón para la sección "Registros tipo 2" -->
        <button wire:click="cambiarSeccion('registro_tipo_2')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Segundo Registro
        </button>
        <!-- Botón para la sección "Registros tipo 3" -->
        <button wire:click="cambiarSeccion('registro_tipo_3')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Tercer Registro
        </button>
        <!-- Botón para la sección "Registros tipo 3" -->
        <button wire:click="cambiarSeccion('registro_tipo_3')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Cuarto Registro
        </button>
        <!-- Botón para la sección "Registros tipo 3" -->
        <button wire:click="cambiarSeccion('registro_tipo_3')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Pie de registro
        </button>
    </aside>

    <div class="w-full max-w-screen-lg p-8">
            
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
        <!-- Coloca aquí el contenido de la sección "Registro Tipo 1" -->
        <p class="text-indigo-600">SECCION PARA CARGAR ARCHIVO</p>
        <div class="grid grid-cols-2 gap-8">
            <!-- Sección izquierda para el formulario de carga de archivos -->
            <div class="mr-3">
                <div class="fondocolor rounded-lg shadow-lg">
                    <form wire:submit.prevent="cargaArchivo">
                        <div class="bg-gradient px-6 py-3 rounded-md">
                            <h2 class="text-lg font-semibold">Archivo: </h2>
                        </div>
                        <input class="text-white p-4" type="file" wire:model="archivo">
                        <button type="submit"
                            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 m-4 rounded-md mt-4">Cargar
                            Archivo</button>
                    </form>
                </div>
                <div class="relative">
                    <span wire:loading wire:target="archivo" class="absolute bottom-0 right-0 mb-2 mr-2">
                        <span class="cargando-icono"></span>
                    </span>
                    <span wire:loading wire:target="cargaArchivo" class="absolute bottom-0 right-0 mb-2 mr-2">
                        <span class="cargando-icono"></span>
                    </span>
                </div>
            </div>
        </div>
</div>