<?php

namespace App\Http\Livewire;

use Livewire\Component;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Livewire\WithFileUploads;

class ArchivosFrances extends Component
{
    use WithFileUploads;
    public $archivo;
    public $contenidoArchivo;

    public function cargaArchivo()
    {
        
        // Validar el archivo
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
                dd($this->contenidoArchivo);
            } else {
                // Para archivos CSV
                $this->contenidoArchivo = $this->procesarArchivoCSV();
            }
        }
    }

    private function procesarArchivoExcel()
    {
        $reader = IOFactory::createReaderForFile($this->archivo->getRealPath());
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($this->archivo->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
    
        // Obtener todas las filas como un array
        $data = $sheet->toArray();
    
        // Obtener la primera fila como encabezados
        $encabezados = array_shift($data);
    
        // Inicializar el array de datos procesados
        $datosProcesados = [];
    
        // Iterar sobre cada fila
        foreach ($data as $fila) {
            // Inicializar un array asociativo para la fila actual
            $filaProcesada = [];
    
            // Iterar sobre cada celda de la fila
            foreach ($fila as $indice => $valor) {
                // Obtener el encabezado correspondiente a este índice
                $encabezado = $encabezados[$indice] ?? 'Columna_' . ($indice + 1);
    
                // Asignar el valor de la celda al encabezado correspondiente en la fila procesada
                $filaProcesada[$encabezado] = $valor;
            }
    
            // Agregar la fila procesada al array de datos procesados
            $datosProcesados[] = $filaProcesada;
        }
    
        return $datosProcesados;
    }

    // Método para procesar un archivo CSV
    private function procesarArchivoCSV()
    {
        return array_map('str_getcsv', file($this->archivo->getRealPath()));
    }

    public function render()
    {
        return view('livewire.archivos-frances');
    }
}
