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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ZipArchive;

class Cobranzas extends Component
{
    use WithFileUploads;

    public $archivo;
    public $contenidoArchivo = [];
    public $datosDuplicados=[];
    public $clientesNoEncontrados=[];
    public $clientesEncontrados=[];
    public $porPagina = 20;
    public $numeroOperacion;
    public $cliCuit;
    public $ultimaReciboCliente;
    public $ultimaRecivoFecha;
    public $clinombre;
    public $sinFactura = [];
    public $numerosGenerados = [];
    public $cliID;
    public $fecha;
    public $recibosCliente = [];

    public function index(){
    return DB::table('dbo.QRY_VENTASCOBROS')
    ->where('CLI_CUIT', '=', '30516492747')
    ->select(['CVECLI_CODIN','SIV_CODDGI','SIV_DESC','CLI_CUIT', 'IdentComp', 'CVE_FEMISION', 'CLI_RAZSOC', 'SCV_ESTADO', 'TAL_DESC'])
    ->orderBy('CVE_FEMISION', 'asc')
    ->get();

    return view('dashboard'/* , ['datosVentasCobros' => $datosVentasCobros] */);
    }
    
    public function actualizarTabla()
{
    // Formatear el número de operación con ceros a la izquierda
    $idCliente = str_pad($this->numeroOperacion, 6, '0', STR_PAD_LEFT);

    // Consultar la base de datos
    $datosClientes = $this->consultarBase($idCliente);
    
    // Verificar si hay al menos un cliente en la colección
    if ($datosClientes->isNotEmpty()) {
        // Obtener el primer cliente de la colección
        $primerCliente = $datosClientes->first();
        
        // Obtener el cli_CUIT del primer cliente
        $this->cliCuit = $primerCliente->cli_CUIT;
        $this->clinombre = $primerCliente->cli_RazSoc;
        $this->cliID = $primerCliente->cli_Cod;
        
        $this->recibosCliente = $this->consultarRecibosCliente($this->cliID);

        if($this->cliID && $this->fecha){
            // Consultar el último recibo del cliente utilizando el cli_CUIT y la fecha seleccionada
            $ultimaReciboCliente = DB::table('dbo.QRY_VENTASCOBROS')
            ->select('CVE_FEMISION', 'IdentComp')
            ->where('CVECLI_CODIN', $this->cliID)
            ->where('IdentComp', 'like', 'RC%')
            ->whereDate('CVE_FEMISION', $this->fecha)
            ->orderBy('CVE_FEMISION', 'desc') // Ordenar primero por fecha en orden descendente
            ->orderBy('IdentComp', 'desc') // Luego ordenar por identificador de recibo en orden descendente
            ->first();
                
                if ($ultimaReciboCliente) {
                    $this->ultimaReciboCliente = $ultimaReciboCliente->IdentComp;
                    $this->ultimaRecivoFecha = $ultimaReciboCliente->CVE_FEMISION;
                } else {
                    // Si no se encuentra ningún recibo para la fecha seleccionada
                    $this->ultimaReciboCliente = null;
                    $this->ultimaRecivoFecha = null;
                }
        } elseif($this->cliID){
            $ultimaReciboCliente = DB::table('dbo.QRY_VENTASCOBROS')
                ->select('CVE_FEMISION', 'IdentComp')
                ->where('CVECLI_CODIN', $this->cliID)
                ->where('IdentComp', 'like', 'RC%')
                ->orderBy('CVE_FEMISION', 'desc')
                ->first();
                
                if ($ultimaReciboCliente) {
                    $this->ultimaReciboCliente = $ultimaReciboCliente->IdentComp;
                    $this->ultimaRecivoFecha = $ultimaReciboCliente->CVE_FEMISION;
                } else {
                    // Si no se encuentra ningún recibo para la fecha seleccionada
                    $this->ultimaReciboCliente = null;
                    $this->ultimaRecivoFecha = null;
                }
        }
    }
}

    public function consultarRecibosCliente($idCliente){
        return DB::table('dbo.QRY_VENTASCOBROS')
    ->where('CVECLI_CODIN', '=',$idCliente)
    ->select(['CVE_FEMISION','IdentComp'])
    ->orderBy('CVE_FEMISION', 'asc')
    ->get();
    }

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

            // Emitir un mensaje de éxito
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

    $contenido = [];
    $this->numerosGenerados = [];
    $encabezados = [];
    $numerosGenerados = [];
    $primerosNumeros = substr($this->ultimaReciboCliente, 3, 5);
    $ultimoNumeroReciboGeneral = intval(substr($this->ultimaReciboCliente, 11, 8));
    $ultimoNumeroReciboPorCliente = [];

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

                        // Obtiene la ultima factura del cliente
                        $ultimaFacturaCliente = DB::table('dbo.QRY_VENTASCOBROS')
                        ->select('CVE_FEMISION', 'IdentComp')
                        ->where('CVECLI_CODIN', $cliente->cli_Cod)
                        ->where('IdentComp', 'like', 'FC%')
                        ->orderBy('CVE_FEMISION', 'desc')
                        ->first();
                        
                        if (!$ultimaFacturaCliente) {
                            // El cliente no tiene facturas, agregar a la lista y continuar con el siguiente cliente
                            $this->sinFactura[] = [
                                'ID' => $idCliente,
                                'Nombre' => optional($cliente)->cli_RazSoc,  // Puedes ajustar el campo Nombre según tu estructura de datos
                            ];
                            continue;  // Salir del bucle actual y pasar al siguiente cliente
                        }

                        $ultimaFactura = $ultimaFacturaCliente->IdentComp;
                        $ultimaFacturaClienteFecha = $ultimaFacturaCliente->CVE_FEMISION;
                        $carbonDate = \Carbon\Carbon::parse($ultimaFacturaClienteFecha);
                        
                        // formatear a fecha de tipo aaaammdd
                        $fechaFormateada = $carbonDate->format('Ymd');
                        
                        // Verificar si se encontró un cliente antes de asignar valores
                        if ($cliente) {
                            
                            $ultimoNumeroReciboGeneral = $ultimoNumeroReciboGeneral + 1;
                            $this->numerosGenerados[] = $ultimoNumeroReciboGeneral;
                            // Formar el nuevo IdentComp
                            $nuevoIdentComp = 'RC ' . $primerosNumeros . '-' . str_pad($ultimoNumeroReciboGeneral, 8, '0', STR_PAD_LEFT);

                            // Utilizar $nuevoIdentComp como sea necesario
                            $ultimaFacturaFecha = $this->ultimaRecivoFecha;
                            $ultimaFacturaIdentComp = $nuevoIdentComp;

                            // Obtener la dirección y la localidad del cliente
                            $direccion = $cliente->cli_Direc;
                            $localidad = $cliente->cli_Loc;

                            if (strpos($direccion, ',') !== false) {
                                // Dividir la dirección usando la coma y obtener la segunda parte
                                $partesDireccion = explode(',', $direccion);
                                $posibleLocalidad = trim($partesDireccion[1]);
                            
                                // Verificar si la segunda parte de la dirección es "CABA"
                                if (strcasecmp($posibleLocalidad, "CABA") === 0) {
                                    // Si es "CABA", establecer la localidad como "CABA"
                                    $localidad = "CABA";
                                } elseif (empty($localidad) || !$localidad) { // Ajuste aquí
                                    // Si $localidad está vacío o no tiene valor asignado, establecerlo como la parte después de la coma en la dirección
                                    $localidad = $posibleLocalidad;
                                }
                            }
                    
                            // Agregar los datos del cliente al array $rowContent
                            $rowContent = array_merge($rowContent, [
                                'ID' => $cliente->cli_Cod,
                                'CUIT' => $cliente->cli_CUIT,
                                'RSOC' => $cliente->cli_RazSoc, 
                                'DIRECCION' => $direccion,
                                'LOCALIDAD' => $localidad,
                                'ULTIMA_FACTURA' => $fechaFormateada,
                                'ULTIMO_RECIBO_IDENTCOMP' => $ultimaFacturaIdentComp,
                                'ULTIMA_FACTURA_IDENTCOMP' => $ultimaFactura,
                            ]);
                        }
                    }
                }
            }
        }

        // Agregar la fila solo si no es la primera fila (encabezados)
        if ($row->getRowIndex() > 1 && !empty(array_filter($rowContent))) {
            // Buscar el cliente correspondiente en el array de clientesEncontrados
/*             $cliente = collect($this->clientesEncontrados)->where('email', $rowContent['CLIENTE'])->first();
 */            
            // Verificar si se encontró un cliente antes de asignar valores
            $idCliente = optional($cliente)->id;
            
            $razonSocialCliente = optional($cliente)->razon_social;
            
            $contenido[] = array_merge($rowContent);
        }
    }  
    
    // Después de procesar todos los clientes, generamos el archivo Excel con los números generados
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'Números Generados');

    // Llenar las celdas con los números generados
    foreach ($numerosGenerados as $index => $numero) {
        $sheet->setCellValue('A' . ($index + 2), $numero);
    }

    // Crear el archivo Excel en un directorio temporal
    $tempFilePath = tempnam(sys_get_temp_dir(), 'numeros_generados_');
    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save($tempFilePath);

    // Descargar el archivo Excel como respuesta
    $this->descargarArchivoNumeros($tempFilePath);

    // Retornar el contenido
    return $contenido;
}

    public function descargarNumerosExcel()
    {
        // Crear un nuevo objeto Spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Agregar los números generados a la hoja de cálculo
        foreach ($this->numerosGenerados as $index => $numero) {
            $sheet->setCellValue('A' . ($index + 1), $numero);
        }

        // Crear el archivo Excel en un directorio temporal
        $tempFilePath = tempnam(sys_get_temp_dir(), 'numeros_generados_');
        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tempFilePath);

        // Descargar el archivo Excel como respuesta
        return response()->download($tempFilePath, 'numeros_generados.xlsx')->deleteFileAfterSend(true);
    }

public function descargarArchivoNumeros($tempFilePath)
{
    // Descargar el archivo Excel como respuesta
    return response()->download($tempFilePath, 'numeros_generados.xlsx')->deleteFileAfterSend(true);
}
    public function consultarBase($id){
        // Obtener la informacion del cliente por su id
        $query = DB::table('clientes')->where('cli_Cod','=',$id)->get();
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

    //**************************** */
    //  Descargar archivos en un .ZIP
    //**************************** */

    public function descargarArchivoTxt()
    {
        // Crear un archivo ZIP
        $zipFile = tempnam(sys_get_temp_dir(), 'archivos_descargados');
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE);
    
        // Llamar a funciones para generar contenido y agregarlo al ZIP
        $archivo1Contenido = $this->generarContenidoArchivo1();
        $archivo1Contenido = iconv("UTF-8", "Windows-1252", $archivo1Contenido);
        $this->agregarArchivoAlZip($zip, $archivo1Contenido, 'VMedPago.txt');
    
        $archivo2Contenido = $this->generarContenidoArchivo2();
        $archivo2Contenido = iconv("UTF-8", "Windows-1252", $archivo2Contenido);
        $this->agregarArchivoAlZip($zip, $archivo2Contenido, 'VCabecer.txt');
    
        $archivo3Contenido = $this->generarContenidoArchivo3();
        $archivo3Contenido = iconv("UTF-8", "Windows-1252", $archivo3Contenido);
        $this->agregarArchivoAlZip($zip, $archivo3Contenido, 'VRelacCo.txt');
    
        // Agregar archivo vacío 'VRegEsp.txt' al ZIP
        $this->agregarArchivoAlZip($zip, '', 'VRegEsp.txt');
    
        // Cerrar el ZIP después de agregar todos los archivos
        $zip->close();
    
        // Descargar el archivo ZIP
        return response()
            ->download($zipFile, 'archivos_descargados.zip', [
                'Content-Type' => 'application/zip; charset=Windows-1252',
                'Content-Disposition' => 'attachment; filename=archivos_descargados.zip',
                'Content-Transfer-Encoding' => 'binary',
            ])
            ->deleteFileAfterSend();
    }

    // Función para generar contenido del primer archivo
    // archivo medPAGO.
    private function generarContenidoArchivo1()
    {
        // Guardar la configuración regional actual
        $configuracionRegionalActual = localeconv();

        // Establecer la configuración regional a una que utilice el punto como separador decimal
        setlocale(LC_NUMERIC, 'en_US.utf8');

        // Generar el contenido del archivo TXT
        $contenidoTxt = "";
        $espaciosEntreCuitYImpacta = str_pad('CAJA03', 9,' ',STR_PAD_RIGHT);
        $esp8 = str_repeat(' ', 8);
        $esp9 = str_repeat(' ', 9);
        $espaciosImporte = str_repeat(' ', 210);
        $uni = str_pad('UNI', '5', ' ', STR_PAD_LEFT);

        foreach ($this->contenidoArchivo as $linea) {
            // Formatear OPERACION con una longitud de 24
            /* $operacion = 'RC   ' . str_pad($linea['ULTIMA_FACTURA_IDENTCOMP'], 19, ' '); */
            $operacion = $linea['ULTIMO_RECIBO_IDENTCOMP'];

            // Eliminar guiones
            $operacion = str_replace('-', '', $operacion);

            // Separar el código de recibo y el número
            $prefix = substr($operacion, 0, 3); // Obtener "RC"
            $codigoRecibo = substr($operacion, 3, 5); // Obtener el código de recibo
            $codigoRecibo = ltrim($codigoRecibo, '0');
            $codigoRecibo = str_pad($codigoRecibo, 4, '0', STR_PAD_LEFT);
            $numeroRecibo = substr($operacion, 8); // Obtener el número de recibo sin el primer 0
            $numeroRecibo = str_pad($numeroRecibo, 8, '0', STR_PAD_LEFT);
            // Construir el nuevo formato
            $nuevoOperacion = $prefix . ' ' . $codigoRecibo . $numeroRecibo;

            $id = $linea['ID'];
            $id = str_pad($id, '6', '0', STR_PAD_LEFT) . '11';

            // Formatear IMPACTA con una longitud de 8 (formato aaaammdd)
            $impacta = \Carbon\Carbon::parse($linea['IMPACTA'])->format('Ymd');
            /* $impacta = str_pad($impacta, 8, ' '); */

            // Convertir IMPORTE a un número de punto flotante
            $importe = floatval(str_replace(',', '.', str_replace('.', '', $linea['IMPORTE'])));

            // Formatear IMPORTE con una longitud de 16 y completar con 0 a la izquierda
            $importe = number_format($importe, 2, '.', '');
            $importe = str_pad($importe, 16, '0', STR_PAD_LEFT);

            // Formatear CUIT con una longitud de 11
            $cuit = str_pad($linea['CUIT'], 8, ' ');

            $contenidoTxt .= "{$nuevoOperacion}{$esp8}{$impacta}{$id}{$uni}{$esp9}{$espaciosEntreCuitYImpacta}{$impacta}{$importe}{$espaciosImporte}{$importe}\r\n";
        }

        // Convertir el contenido a la codificación de caracteres ANSI
        $contenidoTxt = mb_convert_encoding($contenidoTxt, 'Windows-1252', 'UTF-8');
        return $contenidoTxt;
    }

    // Archivo cabecera
    public function generarContenidoArchivo2(){
        $contenidoTxt= "";
        $esp8 = str_repeat(' ', 8);
        $dig = '1';
        $esp2 = '  ';
        $ceros = str_repeat('0', 13);
        $esp14 = str_repeat(' ',14);
        $ceros5 = str_repeat('0',5);
        $esp4 = str_repeat(' ',4);
        $ceros4 = str_repeat('0',4);
        $esp7 = str_repeat(' ',7);
        $esp6 = str_repeat(' ',6);
        $cod = str_pad('00001', 8, ' ', STR_PAD_RIGHT);
        $s = str_pad('S','87',' ' ,STR_PAD_RIGHT);
         // Guardar la configuración regional actual
        $configuracionRegionalActual = localeconv();
        $ceros40= str_repeat('0', 40);
        $esp23 = str_repeat(' ',23);
        $guion= '-';

        // Establecer la configuración regional a una que utilice el punto como separador decimal
        setlocale(LC_NUMERIC, 'en_US.utf8');

        foreach ($this->contenidoArchivo as $linea) {
            $operacion = $linea['ULTIMO_RECIBO_IDENTCOMP'];

            // Eliminar guiones
            $operacion = str_replace('-', '', $operacion);

            $factura = $linea['ULTIMA_FACTURA_IDENTCOMP'];
            $dig = '';

            if (strpos($factura, 'FC B') !== false) {
                // Si la factura contiene 'FC B', asignar '5' a $dig
                $dig = '5';
                $cliTipo = '3'; // Consumidor final
            } elseif (strpos($factura, 'FC A') !== false) {
                // Si la factura contiene 'FC A', asignar '1' a $dig
                $dig = '1';
                $cliTipo = '1';// Cliente Inscripto
            }

            // Separar el código de recibo y el número
            $prefix = substr($operacion, 0, 3); // Obtener "RC"
            $codigoRecibo = substr($operacion, 3, 5); // Obtener el código de recibo
            $codigoRecibo = ltrim($codigoRecibo, '0');
            $codigoRecibo = str_pad($codigoRecibo, 4, '0', STR_PAD_LEFT);
      
            $numeroRecibo = substr($operacion, 8); // Obtener el número de recibo sin el primer 0

            $numeroRecibo = str_pad($numeroRecibo, 8, '0', STR_PAD_LEFT);
            // Construir el nuevo formato
            $nuevoOperacion = $prefix . ' ' . $codigoRecibo . $numeroRecibo;

            $impacta = \Carbon\Carbon::parse($linea['IMPACTA'])->format('Ymd');
            $id = $linea['ID'];
            $id = str_pad($id, '6', '0', STR_PAD_LEFT);
            /* $rsoc= str_pad($linea['RSOC'],41,' ', STR_PAD_RIGHT); */
            $rsoc = $linea['RSOC']; // Razon Social
            // Asegurar que la cadena tenga al menos 41 caracteres
            $rsoc = mb_str_pad($rsoc, 41, ' ', STR_PAD_RIGHT, 'UTF-8');
            $direccion = mb_str_pad($linea['DIRECCION'],38,' ', STR_PAD_RIGHT, 'UTF-8');
            $localidad = mb_str_pad($linea['LOCALIDAD'], 70, ' ', STR_PAD_RIGHT, 'UTF-8');
            $zona= $linea['LOCALIDAD'] == 'CABA'? '1'. $cliTipo : '2'.$cliTipo;
            // Formatear IMPACTA con una longitud de 8 (formato aaaammdd)
            // Convertir IMPORTE a un número de punto flotante
            $importe = floatval(str_replace(',', '.', str_replace('.', '', $linea['IMPORTE'])));
            // Formatear IMPORTE con una longitud de 16 y completar con 0 a la izquierda
            $importe = number_format($importe, 2, '.', '');
            $importe = str_pad($importe, 15, '0', STR_PAD_LEFT);
            $cuit =  $zona . str_pad($linea['CUIT'],'11','0', STR_PAD_LEFT);
            $contenidoTxt .= "{$nuevoOperacion}{$esp8}{$impacta}{$id}{$rsoc}{$dig}{$esp2}{$cuit}{$esp14}{$ceros5}{$esp4}{$ceros4}{$esp7}{$impacta}{$guion}{$importe}{$esp6}{$cod}{$direccion}{$localidad}{$s}{$ceros40}{$esp23}\r\n";
        }
        return $contenidoTxt;
    }
    //
    // archivo relacco
    //
    public function generarContenidoArchivo3(){
        $contenidoTxt= "";
        $esp4 = str_repeat(' ', 4);

        foreach ($this->contenidoArchivo as $linea) {
            $operacion = $linea['ULTIMO_RECIBO_IDENTCOMP'];

            // Eliminar guiones
            $operacion = str_replace('-', '', $operacion);

            // Separar el código de recibo y el número
            $prefix = substr($operacion, 0, 3); // Obtener "RC"
            $codigoRecibo = substr($operacion, 3, 5); // Obtener el código de recibo
            $codigoRecibo = ltrim($codigoRecibo, '0');
            $codigoRecibo = str_pad($codigoRecibo, 4, '0', STR_PAD_LEFT);
      
            $numeroRecibo = substr($operacion, 8); // Obtener el número de recibo sin el primer 0

            $numeroRecibo = str_pad($numeroRecibo, 8, '0', STR_PAD_LEFT);
            // Construir el nuevo formato
            $nuevoOperacion = $prefix . ' ' . $codigoRecibo . $numeroRecibo;

            $fc = $linea['ULTIMA_FACTURA_IDENTCOMP'];
            $fc = str_replace('-', '', $fc);
            $fc = preg_replace('/(?<=A)\s/', '', str_replace(['FCA', ' A'], ['FC A', ' A'], $fc));
            $fc = preg_replace('/(?<=B)\s/', '', str_replace(['FCB', ' B'], ['FC B', ' B'], $fc));
            // Eliminar un cero específico
            $fc = preg_replace('/(?<=B)0/', '', $fc, 1);
            $fc = preg_replace('/(?<=A)0/', '', $fc, 1);
            if(empty($fc)){
                $fc= str_repeat(' ',17);
            }
            $impacta = \Carbon\Carbon::parse($linea['IMPACTA'])->format('Ymd');
            $impacta2 = str_pad($linea['ULTIMA_FACTURA'],'12',' ',STR_PAD_RIGHT);
            $id = $linea['ID'];
            $id = str_pad($id, '6', '0', STR_PAD_LEFT);
            $importe = floatval(str_replace(',', '.', str_replace('.', '', $linea['IMPORTE'])));
            // Formatear IMPORTE con una longitud de 16 y completar con 0 a la izquierda
            $importe = number_format($importe, 2, '.', '');
            $importe = str_pad($importe, 16, '0', STR_PAD_LEFT);
            /* $factura = $linea['ULTIMO_RECIBO_IDENTCOMP'];
            $factura = str_replace([' ', '-'], '', $factura);
            $factura = substr($factura, 0, 2) . ' ' . substr($factura, 2); */
            /* $factura = str_pad($factura,28,' ',STR_PAD_RIGHT); */
            $dAct = Carbon::now()->format('Ymd');

            $contenidoTxt .= "{$nuevoOperacion}{$impacta}{$esp4}{$impacta}{$id}{$fc}{$impacta2}{$impacta}{$importe}\r\n";
        }
        return $contenidoTxt;
    }

    private function agregarArchivoAlZip($zip, $contenido, $nombreArchivo)
    {
        $archivoTemporal = tempnam(sys_get_temp_dir(), 'archivo_temp');
        file_put_contents($archivoTemporal, $contenido);
        $zip->addFile($archivoTemporal, $nombreArchivo);
    }

    public function render()
    {
        return view('livewire.cobranzas');
    }
}
