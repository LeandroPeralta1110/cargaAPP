<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Date;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory; // Importar la clase IOFactory
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Illuminate\Support\Carbon;
use App\Models\client;

class Cobranzas extends Component
{
    use WithFileUploads;

    public $archivo;
    public $contenidoArchivo = [];
    public $datosDuplicados=[];
    public $clientesNoEncontrados=[];
    public $clientesEncontrados=[];
    public $porPagina = 20;

    public function cargarArchivo()
{
    $this->validate([
        'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
    ]);

    // Verificar si se ha seleccionado un archivo
    if ($this->archivo) {
        // Obtener la extensión del archivo
        $extension = $this->archivo->getClientOriginalExtension();
        // Procesar el contenido del archivo según la extensión
        if ($extension === 'xlsx' || $extension === 'xls') {
            // Para archivos Excel
            $this->contenidoArchivo = $this->procesarArchivoExcel();
        } else {
            // Para archivos CSV
            $this->contenidoArchivo = $this->procesarArchivoCSV();
           
        }

        /* // Detectar y almacenar datos duplicados
        $datosDuplicados = $this->detectarDatosDuplicados($this->contenidoArchivo);

        // Obtener correos electrónicos del archivo
        $correosArchivo = array_column($this->contenidoArchivo, 'Cliente');

        // Obtener clientes que tienen correos electrónicos en la lista
        $clientesEncontrados = Client::whereIn('email', $correosArchivo)->pluck('email')->toArray();

        // Obtener clientes no encontrados
        $clientesNoEncontrados = array_diff($correosArchivo, $clientesEncontrados);

        // Filtrar los emails duplicados para mostrar solo uno por cliente
        $clientesNoEncontrados = array_unique($clientesNoEncontrados);

        // Almacenar clientes no encontrados en una variable de componente
        $this->clientesNoEncontrados = $clientesNoEncontrados; */

        // Otra lógica que necesites hacer después de procesar el archivo

        // Emitir un mensaje de éxito (opcional)
        $this->emit('archivoProcesado', 'El archivo se ha procesado correctamente.');

        // Almacenar datos duplicados en una variable de componente
        /* $this->datosDuplicados = $datosDuplicados; */
    }
}

    /* protected function detectarDatosDuplicados($contenido)
    {
        // Inicializar un array para almacenar los datos duplicados
        $datosDuplicados = [];

        foreach ($contenido as $datos) {
            $numeroOperacion = $datos['Operacion'];

            // Agregar los datos al array correspondiente al número de operación
            $datosDuplicados[$numeroOperacion][] = [
                'Impacta' => $datos['Impacta'],
                'Cliente' => $datos['Cliente'],
                'Importe' => $datos['Importe'],
            ];
        }

        // Filtrar solo aquellos con más de una entrada (duplicados)
        $datosDuplicados = array_filter($datosDuplicados, function ($duplicados) {
            return count($duplicados) > 1;
        });

        return $datosDuplicados;
    } */

   /*  public function eliminarDuplicados()
{
    // Filtrar el contenido para mantener solo los no duplicados
    $contenidoSinDuplicados = collect($this->contenidoArchivo)->reject(function ($item) {
        return isset($this->datosDuplicados[$item['Operacion']]);
    })->toArray();

    // Actualizar la variable de componente con los datos sin duplicados
    $this->contenidoArchivo = $contenidoSinDuplicados;

    // Emitir un mensaje de éxito (opcional)
    $this->emit('duplicadosEliminados', 'Los datos duplicados se han eliminado correctamente.');
} */

protected function procesarArchivoExcel()
{
    // Utilizar PhpSpreadsheet para cargar el archivo Excel
    $spreadsheet = IOFactory::load($this->archivo->getRealPath());

    // Obtener la hoja activa del archivo Excel
    $sheet = $spreadsheet->getActiveSheet();

    // Obtener las filas como un array asociativo
    $contenido = [];
    $encabezados = [];

    foreach ($sheet->getRowIterator() as $row) {
        $cellIterator = $row->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(FALSE); // Permitir celdas vacías
    
        $rowContent = [];
    
        foreach ($cellIterator as $index => $cell) {
            // Obtener el valor formateado de la celda
            $cellValue = $cell->getFormattedValue();
    
            // La primera fila se trata como encabezados
            if ($row->getRowIndex() === 1) {
                $encabezados[$index] = !empty($cellValue) ? $cellValue : "Columna_$index";
            } else {
                // Las filas subsiguientes se tratan como datos
                $currentHeader = $encabezados[$index] ?? "Columna_$index";
                $isDateColumn = $this->esColumnaFecha($currentHeader);
    
                // Convertir fechas solo si es una columna de fechas
                if ($isDateColumn) {
                    $formattedDate = Carbon::parse($cellValue)->format('Y-m-d');
                    $rowContent[$currentHeader] = $formattedDate;
                } else {
                    $rowContent[$currentHeader] = $cellValue;

                    // Quitar el símbolo de peso del campo 'IMPORTE'
                    if ($currentHeader === 'IMPORTE') {
                        $rowContent[$currentHeader] = str_replace('$ ', '', $cellValue);
                    }
                }
            }
        }
    
        // Agregar la fila solo si no es la primera fila (encabezados)
        if ($row->getRowIndex() > 1 && !empty(array_filter($rowContent))) {
            // Buscar el cliente correspondiente en el array de clientesEncontrados
            $cliente = collect($this->clientesEncontrados)->where('email', $rowContent['CLIENTE'])->first();
    
            // Verificar si se encontró un cliente antes de asignar valores
            $idCliente = optional($cliente)->id;
            $razonSocialCliente = optional($cliente)->razon_social;
    
            $contenido[] = array_merge($rowContent);
        }
    }    

    return $contenido;
}

    /* public function guardarCliente($email)
{
    // Verificar si el email ya está registrado en la base de datos
    if (!Client::where('email', $email)->exists()) {
        // Asignar un client_id aleatorio entre 0 y 1000
        $client_id = rand(0, 1000);

        // Registrar el cliente en la base de datos con el client_id asignado
        Client::create([
            'client_id' => $client_id,
            'email' => $email,
            'razon_social' => '', // Asigna el valor predeterminado aquí
            'telefono' => '',
        ]);

        // Eliminar el email de la lista de clientes no encontrados
        $this->clientesNoEncontrados = array_diff($this->clientesNoEncontrados, [$email]);

        // Emitir un mensaje de éxito (opcional)
        $this->emit('clienteGuardado', 'El cliente se ha guardado correctamente con client_id: ' . $client_id);
    } else {
        // El email ya está registrado en la base de datos
        // Puedes emitir un mensaje o tomar alguna otra acción
        $this->emit('clienteExistente', 'El cliente ya está registrado en la base de datos.');
    }
} */


    // Función para verificar si una columna es una columna de fechas
    protected function esColumnaFecha($header)
    {
        // Lista de encabezados que representan columnas de fechas
        $columnasFecha = ['Impacta', 'Pago'];

        // Verificar si el encabezado está en la lista de columnas de fechas
        return in_array($header, $columnasFecha);
    }


    protected function procesarArchivoCSV()
{
    $contenido = file($this->archivo->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // Procesar cada línea omitiendo la primera fila (encabezados)
    $resultados = [];
    foreach ($contenido as $index => $linea) {
        // Omitir la primera fila (encabezados)
        if ($index === 0) {
            continue;
        }

        $datos = str_getcsv($linea, ';');

        // Asegurarse de que haya suficientes datos para procesar
        if (count($datos) >= 9) {
            // Convertir la cadena de bytes a UTF-8
            $datos[3] = utf8_encode($datos[3]);

            // Quitar el símbolo de peso del campo 'IMPORTE'
            $datos[5] = str_replace('$ ', '', $datos[5]);

            $resultados[] = [
                'SERV.' => $datos[0],
                'IMPACTA' => $datos[1],
                'CLIENTE' => $datos[2],
                'SUSCRIPCION' => $datos[3],
                'OPERACIÓN' => $datos[4],
                'IMPORTE' => $datos[5],
                'PAGO' => $datos[6],
                'ID' => $datos[7],
                'RAZON SOCIAL' => $datos[8],
            ];
        }
    }

    return $resultados;
}

    public function render()
    {
        return view('livewire.cobranzas');
    }
}
