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

class Cobranzas extends Component
{
    use WithFileUploads;

    public $archivo;
    public $contenidoArchivo = [];
    public $datosDuplicados=[];

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

        // Detectar y almacenar datos duplicados
        $datosDuplicados = $this->detectarDatosDuplicados($this->contenidoArchivo);

        // Otra lógica que necesites hacer después de procesar el archivo

        // Emitir un mensaje de éxito (opcional)
        $this->emit('archivoProcesado', 'El archivo se ha procesado correctamente.');

        // Almacenar datos duplicados en una variable de componente
        $this->datosDuplicados = $datosDuplicados;
    }
}

    protected function detectarDatosDuplicados($contenido)
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
    }

    public function eliminarDuplicados()
{
    // Filtrar el contenido para mantener solo los no duplicados
    $contenidoSinDuplicados = collect($this->contenidoArchivo)->reject(function ($item) {
        return isset($this->datosDuplicados[$item['Operacion']]);
    })->toArray();

    // Actualizar la variable de componente con los datos sin duplicados
    $this->contenidoArchivo = $contenidoSinDuplicados;

    // Emitir un mensaje de éxito (opcional)
    $this->emit('duplicadosEliminados', 'Los datos duplicados se han eliminado correctamente.');
}

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
                    }
                }
            }

            // Agregar la fila solo si no es la primera fila (encabezados)
            if ($row->getRowIndex() > 1 && !empty(array_filter($rowContent))) {
                $contenido[] = $rowContent;
            }
        }

        return $contenido;
    }

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

        dd( $contenido);
        // Procesar cada línea utilizando str_getcsv y formatear los datos
        $resultados = [];
        foreach ($contenido as $linea) {
            $datos = str_getcsv($linea, ',');

            // Asegurarse de que haya suficientes datos para procesar
            if (count($datos) >= 6) {
                $resultados[] = [
                    'Impacta' => $datos[0],
                    'Cliente' => $datos[1],
                    'Subscripcion' => $datos[2],
                    'Operacion' => $datos[3],
                    'Importe' => $datos[4],
                    'Pago' => $datos[5],
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
