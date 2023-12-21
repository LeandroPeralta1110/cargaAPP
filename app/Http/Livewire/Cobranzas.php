<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Date;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Illuminate\Support\Carbon;
use App\Models\client;
use Illuminate\Support\Facades\DB;

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
    $datos = [];
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
                     // Obtener ID de cliente de la columna 'ID'
                     if ($currentHeader === 'ID') {
                        $idCliente = $cellValue;
                        $idCliente = str_pad($cellValue, 6, '0', STR_PAD_LEFT);

                        // Realizar la consulta a la base de datos
                        $clienteCollection = $this->consultarBase($idCliente);

                        // Obtener el primer elemento de la colección (Illuminate\Support\Collection)
                        $cliente = $clienteCollection->first();

                        // Verificar si se encontró un cliente antes de asignar valores
                        if ($cliente) {
                            // Agregar los datos del cliente al array $rowContent
                            $rowContent = array_merge($rowContent, [
                                'CUIT' => $cliente->cli_CUIT,
                                // Otros campos según sea necesario
                            ]);
                        }
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

public function consultarBase($id){
    $query=DB::table('clientes')->where('cli_Cod','=',$id)->get();
    return $query;
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

public function descargarArchivoTxt()
{
    // Guardar la configuración regional actual
    $configuracionRegionalActual = localeconv();

    // Establecer la configuración regional a una que utilice el punto como separador decimal
    setlocale(LC_NUMERIC, 'en_US.utf8');

    // Generar el contenido del archivo TXT
    $contenidoTxt = "";
    $espaciosEntreCuitYImpacta = str_repeat(' ', 23);
    $espaciosImporte = str_repeat(' ', 199);

    foreach ($this->contenidoArchivo as $linea) {
        // Formatear OPERACION con una longitud de 24
        $operacion = str_pad($linea['OPERACIÓN'], 24, ' ');
        $id = $linea['ID'];
        $id = str_pad($id,'11',' ',STR_PAD_LEFT);

        // Formatear IMPACTA con una longitud de 8 (formato aaaammdd)
        $impacta = \Carbon\Carbon::parse($linea['IMPACTA'])->format('Ymd');
        $impacta = str_pad($impacta, 8, ' ');

        // Convertir IMPORTE a un número de punto flotante
        $importe = floatval(str_replace(',', '.', str_replace('.', '', $linea['IMPORTE'])));

        // Formatear IMPORTE con una longitud de 16 y completar con 0 a la izquierda
        $importe = number_format($importe, 2, '.', '');
        $importe = str_pad($importe, 16, '0', STR_PAD_LEFT);

        // Formatear CUIT con una longitud de 11
        $cuit = str_pad($linea['CUIT'], 11, ' ');

        $contenidoTxt .= "{$operacion}{$impacta}{$cuit}{$espaciosEntreCuitYImpacta}{$impacta}{$importe}{$id}{$espaciosImporte}{$importe}\r\n";
    }

      // Convertir el contenido a la codificación de caracteres ANSI
      /* $contenidoTxt = iconv('UTF-8', 'Windows-1252', $contenidoTxt); */
    $contenidoTxt = mb_convert_encoding($contenidoTxt, 'Windows-1252', 'UTF-8');

      // Agregar BOM al inicio del archivo
      /* $bom = "\xEF\xBB\xBF";
      $contenidoTxt = $bom . $contenidoTxt; */
  
      // Definir el nombre del archivo
      $nombreArchivo = 'contenido_archivo.txt';
  
      // Almacenar el contenido en un archivo temporal
      $rutaTemporal = tempnam(sys_get_temp_dir(), 'archivo_txt_temp');
      file_put_contents($rutaTemporal, $contenidoTxt);
  
      // Descargar el archivo con las cabeceras adecuadas
      return response()
          ->download($rutaTemporal, $nombreArchivo, [
              'Content-Type' => 'text/plain; charset=Windows-1252',
              'Content-Disposition' => 'attachment; filename=' . $nombreArchivo,
              'Content-Transfer-Encoding' => 'binary',
          ])
          ->deleteFileAfterSend();
  }

    public function render()
    {
        return view('livewire.cobranzas');
    }
}