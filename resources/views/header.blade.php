<div>
    <aside class="w-1/7 h-screen p-6 flex flex-col bg-gradient">
        <!-- Botones para cambiar de sección -->
        <button wire:click="cambiarSeccion('alta_proveedor')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Alta Proveedores
        </button>
        <button wire:click="cambiarSeccion('registro_tipo_1')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Registros tipo 1
        </button>
        <button wire:click="cambiarSeccion('registro_tipo_2')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Registros tipo 2
        </button>
        <button wire:click="cambiarSeccion('registro_tipo_3')"
            class="bg-blue-500 hover:bg-blue-600 text-white custom-btn px-4 py-2 rounded-md mb-2">Registros tipo 3
        </button>
    </aside>
</div>