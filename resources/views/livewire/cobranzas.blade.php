<div class="flex bg-cover bg-center bg-fixed imagenfondo">
    <div class="grid grid-cols-2 gap-8">
        <div class="fondocolor rounded-lg shadow-lg">
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
    </div>

    <div class="grid grid-cols-2 gap-8">
        <div class="fondocolor rounded-lg shadow-lg">
            <div class="bg-gradient px-6 py-3 rounded-md">
                <h2 class="text-lg font-semibold">Datos duplicados </h2>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="min-w-full">
                    <thead>
                        <tr>
                <th>Impacta</th>
                <th>Cliente</th>
                <th>importe</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-8">
        <div class="fondocolor rounded-lg shadow-lg">
            <div class="bg-gradient px-6 py-3 rounded-md">
                <h2 class="text-lg font-semibold">Clientes no encontrados</h2>
            </div>
        </div>
    </div>
</div>
