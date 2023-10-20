<div>
    @if($registrosArchivos)
    <div class="mr-3 w-2/12 p-4 bg-gray-200 rounded-2xl" style="position: absolute; right: 0; top: 56%; height: 300px;">
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
</div>
