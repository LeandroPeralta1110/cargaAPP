<?php
//Componente donde se ecuentra toda la logica de el programa, tanto la carga, descarga y eliminacion de archivos.

namespace App\Http\Livewire;
use App\Helpers\Expressions;
use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CargaArchivo extends Component
{
    use WithFileUploads;

    protected $listeners = ['datosTipo2Cargados' => 'cargaArchivoTipo3'];
    protected $identificador;

    public $datosAltaProveedor = [];
    public $datosArchivoPago = [];
    public $datosProcesadosTipo1 = [];
    public $datosProcesadosTipo2 = [];
    public $datosProcesadosTipo3 = [];
    public $datosProcesados = [];
    public $datosParaTipo3 =[];
    public $registrosArchivos = [];
    public $nombreArchivo= [];
    public $datosArchivo= [];
    public $ultimoRegistro = [];
    public $ultimosRegistros = [];
    public $registrosArchivosTipo1 = [];
    public $registrosArchivosTipo2 = [];
    public $identificadorUnicoCargadoTipo2;
    public $datosCargadosTipo2 = [];
    public $identificadorTipo2;
    private $expresionesRegulares = [];

    public $ultimoArchivo = [];
    public $cantidadDatos = 0; 

    public $ultimaFilaTipo3=[];
    public $totalImporteTipo2;
    public $contadorRegistrosTipo2 = 0;
    public $contadorRegistrosAltaProveedores = 0;

    public $archivo;
    public $contenido;
    public $mostrarDatosAltaProveedor = false;
    public $mostrarDatosArchivoPago = false;
    public $mostrarDatosTipo1 = false;
    public $mostrarDatosTipo2 = false;
    public $mostrarDatosTipo3 = false;
    public $cargandoDatosTipo1 = false;

    public $datos = []; // Array para almacenar los datos procesados
    public $porPagina = 6; // Número de elementos por página
    public $pagina = 1; // Página actual

    //secciones para el tipo de pago, predefinido alta proveedores
    public $seccionSeleccionada = "alta_proveedor";

    public function cargarArchivoAltaProveedor()
    {
        $this->validate([
            'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
        ]);
        $this->mostrarDatosAltaProveedor = false;
        // Obtener el contenido del archivo
        $contenido = file_get_contents($this->archivo->getRealPath());

         // Determinar el tipo de archivo según la extensión
        $extension = $this->archivo->getClientOriginalExtension();

        // Procesar el contenido según la extensión
        if ($extension === 'csv' || $extension === 'txt') {
            // Procesar archivo CSV o TXT
            $lineas = explode("\n", $contenido);
            $this->procesarArchivoTipo1CSVoTXT($lineas);
        }elseif($extension === 'xlsx'){
             // Cargar el archivo Excel y obtener sus datos
            $this->procesarArchivoExcel($this->archivo);
        }
    }

    public function procesarArchivoTipo1CSVoTXT($lineas){
        $datosArchivoActual = [];
        foreach ($lineas as $linea) {
            $datos = str_getcsv($linea, ','); // Dividir la línea en elementos usando la coma como separador

            // Eliminar guiones y espacios en blanco de la cadena de CBU
            $cbu = str_replace(['-', ' '], '', $datos[0]);

            // Asegurarse de que la longitud del CBU sea de 22 caracteres
            $cbu = str_pad($cbu, 22, '0', STR_PAD_LEFT);

            // Determinar el valor de $alias
            if (isset($datos[1])) {
                // El índice 1 existe en $datos, puedes acceder a $datos[1]
                $alias = $datos[1] ? str_repeat('0', 22) : str_repeat(' ', 22);
            } else {
                // El índice 1 no existe en $datos, proporciona un valor predeterminado o maneja el caso de error según sea necesario
                // Por ejemplo, aquí estableceremos un valor predeterminado para $alias
                $alias = str_repeat(' ', 22); // Valor predeterminado si no hay un valor en $datos[1]
            }

           // Verificar si $datos tiene al menos 3 elementos antes de acceder a $datos[2]
            if (isset($datos[2])) {
                // El índice 2 existe en $datos, puedes acceder a $datos[2]
                $idTipo = str_pad($datos[2], 1);
            }

            if(isset($datos[3])){
               // Eliminar caracteres especiales y asegurarse de que la longitud de la clave de cuenta sea de 11 caracteres
            $claveCuenta = preg_replace('/[^0-9]/', '', $datos[3]);
            $claveCuenta = str_pad($claveCuenta, 11, '0', STR_PAD_LEFT); 
            }
            
            if(isset($datos[4])){
            $tipoCuenta = str_pad($datos[4], 2);
            }

            if(isset($datos[5])){
                $referenciaCuenta = str_pad($datos[5], 30); 
            }
            
            if(isset($datos[6])){
                $email = str_pad($datos[6], 50);
            }
            
            $titulares = '1'; // Valor fijo para Titulares

            // Agregar los datos a la lista
            if ($this->seccionSeleccionada === 'alta_proveedor') {
                $this->datosAltaProveedor[] = [
                    'cbu' => $cbu,
                    'alias' => $alias,
                    'id_tipo' => $idTipo,
                    'clave_cuenta' => $claveCuenta,
                    'tipo_cuenta' => $tipoCuenta,
                    'referencia_cuenta' => $referenciaCuenta,
                    'email' => $email,
                    'titulares' => $titulares,
                ];

                $datosArchivoActual[] = [
                    'cbu' => $cbu,
                    'alias' => $alias,
                    'id_tipo' => $idTipo,
                    'clave_cuenta' => $claveCuenta,
                    'tipo_cuenta' => $tipoCuenta,
                    'referencia_cuenta' => $referenciaCuenta,
                    'email' => $email,
                    'titulares' => $titulares,
                ];
            }
        }
        // Almacenar los últimos registros procesados en $ultimosRegistros
        $this->ultimosRegistros = $datosArchivoActual;

        $this->registrosArchivos[] = [
            'nombre_archivo' => $this->archivo->getClientOriginalName(),
            'tipo_registro' => 'Alta Proveedores',
            'datos' => $datosArchivoActual, // Almacena los datos procesados del archivo actual
        ];
        $this->mostrarDatosAltaProveedor = true;
    }

    public function procesarArchivoExcel($archivo)
    {   
        $spreadsheet = IOFactory::load($archivo);
        $worksheet = $spreadsheet->getActiveSheet();
        $datos = [];

        foreach ($worksheet->getRowIterator() as $row) {
            $fila = [];
            foreach ($row->getCellIterator() as $cell) {
                $datos[] = $cell->getValue();
            }
            
            $cbu = str_replace(['-', ' '], '', $datos[0]);
            $cbu = str_pad($cbu, 22, '0', STR_PAD_LEFT);
            
            $alias = isset($datos[1]) ? ($datos[1] ? str_repeat('0', 22) : str_repeat(' ', 22)) : str_repeat(' ', 22);
        
            $idTipo = isset($datos[2]) ? str_pad($datos[2], 1) : null;
        
            if (isset($datos[3])) {
                $claveCuenta = preg_replace('/[^0-9]/', '', $datos[3]);
                $claveCuenta = str_pad($claveCuenta, 11, '0', STR_PAD_LEFT);
            } else {
                $claveCuenta = null;
            }
        
            $tipoCuenta = isset($datos[4]) ? str_pad($datos[4], 2) : null;
        
            $referenciaCuenta = isset($datos[5]) ? str_pad($datos[5], 30) : null;
        
            $email = isset($datos[6]) ? str_pad($datos[6], 50) : null;
        
            $titulares = '1'; 
            
            // Agregar los datos a la lista (ajusta el nombre de la propiedad según corresponda)
            if ($this->seccionSeleccionada === 'alta_proveedor') {
                $this->datosAltaProveedor[] = [
                    'cbu' => $cbu,
                    'alias' => $alias,
                    'id_tipo' => $idTipo,
                    'clave_cuenta' => $claveCuenta,
                    'tipo_cuenta' => $tipoCuenta,
                    'referencia_cuenta' => $referenciaCuenta,
                    'email' => $email,
                    'titulares' => $titulares,
                ];

                $datosArchivoActual[] = [
                    'cbu' => $cbu,
                    'alias' => $alias,
                    'id_tipo' => $idTipo,
                    'clave_cuenta' => $claveCuenta,
                    'tipo_cuenta' => $tipoCuenta,
                    'referencia_cuenta' => $referenciaCuenta,
                    'email' => $email,
                    'titulares' => $titulares,
                ];
            }
        }
    
    $this->registrosArchivos[] = [
        'nombre_archivo' => $this->archivo->getClientOriginalName(),
        'tipo_registro' => 'Alta Proveedores',
        'datos' => $datosArchivoActual, // Almacena los datos procesados del archivo actual
    ];
    // Establecer $mostrarDatos solo si se cargaron datos en la sección "Alta a Proveedores"
    if ($this->seccionSeleccionada === 'alta_proveedor') {
        $this->mostrarDatosAltaProveedor = true;
    }
}

public function cargaArchivoTipo2()
{
    $this->validate([
        'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
    ]);

    // Obtener el contenido del archivo
    $contenido = file_get_contents($this->archivo->getRealPath());
    $lineas = explode("\n", $contenido);
    $primeraFila = trim($lineas[0]); // Asumiendo que la primera fila contiene el formato

    $expresionesRegulares = [];

    // Generar las expresiones regulares desde la primera fila
    $expresionesRegulares = $this->generarExpresionesRegularesDesdeFila($primeraFila, $expresionesRegulares);

    // Verificar si se generaron correctamente las expresiones regulares
    if (empty($expresionesRegulares) || count($expresionesRegulares) === 0) {
        // Las expresiones regulares no se generaron correctamente, muestra un mensaje de error
        return redirect()->back()->withErrors(['error' => 'No se pudieron generar las expresiones regulares correctamente.']);
    }

    // Ahora que las expresiones regulares se han generado, procede con la carga de datos
    $this->procesarArchivo($lineas, $expresionesRegulares);
}

public function procesarArchivo($lineas, $expresionesRegulares)
{
    $totalImporte = 0;
    $datosCargadosTemporal = [];
    $contadorRegistrosTipo2 = 0;
    $identificadorUnico = uniqid();
    $totalImporteTipo2Formateado = 0;
    $tipoRegistro = '2';
    $entidad = '';
    $sucursal = '';
    $identificadorTipo2 = uniqid();
    $this->identificadorTipo2 = $identificadorTipo2;

    $datosArchivoActual = [];
    $datosFaltantes = [];

    foreach ($lineas as $linea) {
        // Verificar si la línea no está vacía
        if (!empty($linea)) {
            // Dividir la línea en elementos usando el punto y coma como separador
            $datos = explode(';', $linea);

            // Verificar si hay la misma cantidad de campos que expresiones regulares
            if (count($datos) === count($expresionesRegulares)) {
                $datosValidos = true;
                // Itera sobre cada campo y verifica si coincide con su expresión regular correspondiente
                // Itera sobre cada campo y verifica si coincide con su expresión regular correspondiente
                foreach ($datos as $indice => $dato) {
                    $expresionRegular = $expresionesRegulares[$indice];

                    if (preg_match($expresionRegular, $dato, $matches)) {
                        // El dato coincide con el formato esperado, lo almacenamos
                        $datosCapturados[] = $matches[0];
                    } else {
                        // El dato no coincide con el formato esperado
                        $datosValidos = false;
                        $datosFaltantes[] = [
                            'campo' => $indice + 1,
                            'valor' => $dato,
                        ];
                        break;
                    }
                }

                if ($datosValidos) {
                    $entidadAcreditar = 011;
                    $cuentaAcreditar = 599;
    
                    $entidadAcreditar = str_pad($entidadAcreditar, 4, '0', STR_PAD_LEFT);
                    $cuentaAcreditar = str_pad($cuentaAcreditar, 4, '0', STR_PAD_LEFT);
    
                    $cbu = '01105998-30000565884328';
    
                    $partes = explode("-", $cbu);
                    $primerBloque = $partes[0];
                    $segundoBloque = $partes[1];
    
                    // Extraer el último número del primer bloque
                    $ultimoNumeroPrimerBloque = substr($primerBloque, -1);
    
                    // Obtener todos los números del segundo bloque
                    $numerosSegundoBloque = preg_replace("/[^0-9]/", "", $segundoBloque);
    
                    /* if (!empty($fila['sucursal_acreditar'])) {
                        $sucursal = str_pad($datos['sucursal_acreditar'], 4, '0', STR_PAD_LEFT);
                    } */
                    $referencia = 'Pago Proveedores';
    
                    $identificacionCliente = rand(1, 3);
                    $cuil = $datos[2];
                    $cuil = str_pad($cuil, strlen($cuil) + 10);
                    $importeOriginal = $datos[5];
    
                    // Elimina el símbolo "$" y las comas de la cadena de importe
                    $importeLimpio = str_replace(['$', ','], '', $importeOriginal);
    
                    // Convierte la cadena en un número entero positivo
                    $importeEntero = abs((int)$importeLimpio);
    
                    // Divide el número por 100 para obtener el valor en dólares y centavos
                    $importeDecimal = $importeEntero / 100;
    
                    if (!empty($datos[8])) {
                        $datosEmpresa = $datos[8];
                    } else {
                        // Si el último dato de la fila está vacío, llenarlo con 13 espacios en blanco
                        $datosEmpresa = str_pad('', 13);
                    }
    
                    // Formatea el número como moneda, agregando el símbolo de moneda al inicio
                    $importeFormateado = "$" . number_format($importeDecimal, 2, ',', '.');
                    $claseDocumento = '0';
                    $tipoDocumentoBeneficiario = '00';
                    $usoBNA = '00';
                    $nroDocumentoBeneficiario = str_pad('0',11);
                    $cuilConCeros = str_pad('0',11);
                    $estado = '00';
                    $identificadorPrestamo = '0000';
                    $nroOperacionLink = str_pad('0',9);
                    $sucursalAcreditar = '0000';
                    $nroRegistro = str_pad('0',6);
                    $observaciones = str_pad('0',15);
                    $filler = str_pad('0',62);
    
                    $datosArchivoActual[] = $datosCapturados;
                    // Incrementar el contador de registros Tipo 2
                    $contadorRegistrosTipo2++;
                }
            } else {
                // Si los datos no coinciden con el formato actual, actualiza las expresiones regulares
                $expresionesRegulares = $this->generarExpresionesRegularesDesdeFila($linea, $expresionesRegulares);
            }
        }
    }

    // Al final del procesamiento exitoso, agregar los datos cargados a $this->datosProcesadosTipo2
    $this->datosProcesadosTipo2 = array_merge($this->datosProcesadosTipo2, $datosArchivoActual);

    // Agrega una copia de los datos procesados al array $this->registrosArchivos
    $this->registrosArchivos[] = [
        'identificador_tipo2' => $identificadorTipo2,
        'nombre_archivo' => $this->archivo->getClientOriginalName(),
        'tipo_registro' => 'Registros tipo 2',
        'datos' => $datosArchivoActual,
    ];
    // Guardar el total de importe para su uso posterior
    $this->totalImporteTipo2 = $totalImporte;
    $this->mostrarDatosTipo2 = true;

    // Emitir un evento con los datos para cargaArchivoTipo3
    $this->emit('datosTipo2Cargados', $this->totalImporteTipo2, $contadorRegistrosTipo2);
}

public function generarExpresionesRegularesDesdeFila($fila, $expresionesRegulares)
{
    // Verificar si $fila ya es un array
    if (is_array($fila)) {
        // Convertir el array en una cadena de texto uniendo sus elementos con comas
        $fila = implode(',', $fila);
    }

    // Dividir la fila en elementos usando el punto y coma como separador
    $datos = explode(';', $fila);

    // Inicializar un array para almacenar las expresiones regulares y longitudes esperadas
    $expresionesYLongitudes = [];

    // Iterar sobre cada dato en la fila
    foreach ($datos as $indice => $dato) {
        $expresionRegular = null;
        $longitudDato = strlen($dato);

        // Intentar identificar el tipo de dato y generar una expresión regular en consecuencia
        if (is_numeric($dato)) {
            // Si es numérico, expresión regular para números con la longitud esperada
            $expresionRegular = '/^\d{' . $longitudDato . '}$/';
        } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dato)) {
            // Si parece una fecha en formato DD/MM/YYYY
            $expresionRegular = '/^\d{2}\/\d{2}\/\d{4}$/';
        } elseif (strpos($dato, '$') !== false) {
            // Si contiene el símbolo de dólar ($), expresión regular para valores de moneda
            $expresionRegular = '/^\$\d{1,3}(,\d{3})*(\.\d{2})?$/';
        } elseif ($longitudDato > 0) {
            // Si tiene una longitud mayor que 0, expresión regular para letras y caracteres especiales
            $expresionRegular = '/^[A-Za-z0-9\s\.\-]{' . $longitudDato . '}$/';
        }

        // Agregar la expresión regular y la longitud al array
        $expresionesYLongitudes[] = [
            'expresionRegular' => $expresionRegular,
            'longitud' => $longitudDato,
        ];
    }

    // Actualizar el array $expresionesRegulares con las nuevas expresiones regulares y longitudes
    $expresionesRegulares = $expresionesYLongitudes;

    // Aquí puedes guardar $expresionesRegulares en una variable de sesión, en una base de datos, o donde prefieras
    // para su posterior uso con archivos que sigan este formato

    return $expresionesRegulares;
}
     
    public function cargaArchivoTipo3()
    {
            // Verificar si se obtuvieron datos válidos
            $tipoRegistro = "3";

            // Obtener los datos acumulados de cargaArchivoTipo2
            $datosTipo2 = $this->datosProcesadosTipo2;
            $totalImporteTipo2 = 0;
            $totalRegistrosTipo2 = 0;
            $totalImporteTipo2Formateado = '0';

                // Calcular el total de importe y registros de cargaArchivoTipo2
                foreach ($datosTipo2 as $dato) {
                    $totalImporteTipo2 += $dato['importe'];
                    // Formatear $totalImporteTipo2 como cantidad de dinero
                    $totalImporteTipo2Formateado = number_format($totalImporteTipo2, 2, '.', '');
                    $totalImporteTipo2Formateado = str_replace('.', '', $totalImporteTipo2Formateado); // Eliminar el punto
                    $totalImporteTipo2Formateado = str_pad($totalImporteTipo2Formateado, 15, '0', STR_PAD_LEFT); // Rellenar con ceros
                    $totalRegistrosTipo2 = str_pad($totalRegistrosTipo2, 7, '0', STR_PAD_LEFT);
                    $totalRegistrosTipo2++;
                }

                $identificadorTipo2 = $this->identificadorTipo2;

                $importeAceptados = str_repeat('0', 15);

                $cantidadRegistrosTipo2Aceptados = str_repeat('0', 7);

                $importeRechazados = str_repeat('0', 15);

                $cantidadRegistrosTipo2Rechazados = str_repeat('0', 7);

                $importeComisiones = str_repeat('0', 10);

                $importeIVA = str_repeat('0', 10);

                $importeRetencionIVA = str_repeat('0', 10);

                $importeIngresosBrutos = str_repeat('0', 10);

                $ImporteSelladoProvincial = str_repeat('0', 10);

                $filler = str_repeat('0', 83);

                    // Agregar los datos procesados al array
                    $this->datosProcesadosTipo3[] = [
                        'identificador_tipo2' => $identificadorTipo2,
                        'tipo_registro' => $tipoRegistro,
                        'total_importe' => $totalImporteTipo2Formateado,
                        'total_registros' => $totalRegistrosTipo2,
                        'importe_aceptados'=> $importeAceptados,
                        'cantidad_registros_tipo2_aceptados'=> $cantidadRegistrosTipo2Aceptados,
                        'importes_rechazados' => $importeRechazados,
                        'cantidad_registros_tipo2_rechazados' => $cantidadRegistrosTipo2Rechazados,
                        'importe_comision' => $importeComisiones,
                        'importe_IVA' => $importeIVA,
                        'importe_retencion_IVA' => $importeRetencionIVA,
                        'importe_ingreso_bruto' => $importeIngresosBrutos,
                        'importe_sellado_provincial'=> $ImporteSelladoProvincial,
                        'filler' => $filler,
                    ];

                      $this->ultimaFilaTipo3[] = [
                        'identificador_tipo2' => $identificadorTipo2,
                        'tipo_registro' => $tipoRegistro,
                        'total_importe' => $totalImporteTipo2Formateado,
                        'total_registros' => $totalRegistrosTipo2,
                        'importe_aceptados' => $importeAceptados,
                        'cantidad_registros_tipo2_aceptados' => $cantidadRegistrosTipo2Aceptados,
                        'importes_rechazados' => $importeRechazados,
                        'cantidad_registros_tipo2_rechazados' => $cantidadRegistrosTipo2Rechazados,
                        'importe_comision' => $importeComisiones,
                        'importe_IVA' => $importeIVA,
                        'importe_retencion_IVA' => $importeRetencionIVA,
                        'importe_ingreso_bruto' => $importeIngresosBrutos,
                        'importe_sellado_provincial' => $ImporteSelladoProvincial,
                        'filler' => $filler,
                    ];

            $this->mostrarDatosTipo3 = true; 
    }

    public function descargarDatosAltaProveedores()
    {
        // Verifica que la sección actual sea "alta_proveedor" y que haya datos antes de generar el archivo
        if ($this->seccionSeleccionada === 'alta_proveedor' && count($this->datosAltaProveedor) > 0) {
            // Genera el contenido del archivo TXT
            $contenido = '';
            foreach ($this->datosAltaProveedor as $fila) {
                // Formatea los campos según las longitudes
                $contenido .=
                    str_pad($fila['cbu'], 22, '0', STR_PAD_LEFT) .
                    str_pad($fila['alias'], 22) .
                    $fila['id_tipo'] .
                    str_pad($fila['clave_cuenta'], 11, '0', STR_PAD_LEFT) .
                    $fila['tipo_cuenta'] .
                    str_pad($fila['referencia_cuenta'], 30) .
                    str_pad($fila['email'], 50) .
                    $fila['titulares'] . "\n";
            }

            // Agregar el relleno de 134 espacios en blanco
            $contenido .= str_repeat(' ', 134);

            // Agregar la cantidad de registros con longitud fija de 5
            $contenido .= str_pad(count($this->datosAltaProveedor), 5, '0', STR_PAD_LEFT);

            // Define el nombre del archivo
            $nombreArchivo = 'datos_alta_proveedores.txt';

            // Crea el archivo en el almacenamiento temporal
            file_put_contents($nombreArchivo, $contenido);

            // Proporciona una respuesta para descargar el archivo
            return response()->stream(
                function () use ($nombreArchivo) {
                    readfile($nombreArchivo);
                },
                200,
                [
                    'Content-Type' => 'text/plain',
                    'Content-Disposition' => 'attachment; filename=' . $nombreArchivo,
                ]
            );
        }
    }
    
    public function descargarDatosRegistroTipo1()
    {
        // Verifica que la sección actual sea "regitro_tipo1" y que haya datos antes de generar el archivo
        if ($this->seccionSeleccionada === 'registro_tipo_1' && count($this->datosProcesadosTipo1) > 0) {
            // Genera el contenido del archivo TXT
            $contenido = '';
            foreach ($this->datosProcesadosTipo1 as $fila) {
                // Formatea los campos según las longitudes
                $contenido .=
                    $fila['tipo_registro'] .
                    $fila['cuit_empresa'] .
                    $fila['codigo_sucursal'] .
                    $fila['cbu_deseado'] .
                    $fila['moneda'] .
                    $fila['fecha_pago'] .
                    $fila['info_criterio_empresa'] .
                    $fila['tipo_pago'] .
                    $fila['clase_pagos'] .
                    $fila['codigo_convenio'] .
                    $fila['numero_envio'] .
                    $fila['sistema_original'] .
                    $fila['filler'] .
                    $fila['casa_envio_rendicion'] .
                    $fila['filler2'] . "\n";
            }
            // Define el nombre del archivo
            $nombreArchivo = 'datos_registro_tipo_1.txt';

            // Crea el archivo en el almacenamiento temporal
            file_put_contents($nombreArchivo, $contenido);

            // Proporciona una respuesta para descargar el archivo
            return response()->stream(
                function () use ($nombreArchivo) {
                    readfile($nombreArchivo);
                },
                200,
                [
                    'Content-Type' => 'text/plain',
                    'Content-Disposition' => 'attachment; filename=' . $nombreArchivo,
                ]
            );
        }
    }

    public function descargarDatosRegistroTipo2()
{
    // Verifica que haya datos cargados en datosCargadosTipo2
    if (count($this->datosCargadosTipo2) > 0) {
        // Genera el contenido del archivo TXT
        $contenido = '';
        $tipoRegistro = '2';

        foreach ($this->datosCargadosTipo2 as $fila) {
            /* if (!empty($fila['tipo_registro'])) {
                $tipoRegistro = $fila['tipo_registro'];
            }
            if (!empty($fila['entidad_acreditar'])) {
                $entidad = str_pad($fila['entidad_acreditar'], 4, '0', STR_PAD_LEFT);
            }
            if (!empty($fila['sucursal_acreditar'])) {
                $sucursal = str_pad($fila['sucursal_acreditar'], 4, '0', STR_PAD_LEFT);
            } */
            // Elimina los caracteres "$" y ","
            $formatoDinero = $fila['importe_formateado'];  
            $formatoDinero = str_replace(['$', ','], '', $formatoDinero);

            // Convierte la cadena a un número entero
            $numeroEntero = intval($formatoDinero);
            $fila['importe'] = $numeroEntero;

            // Formatea los campos según las longitudes y concatena sin espacios
            $contenido .=
                $fila['tipo_registro'] .
                $fila['entidad_acreditar'].
                $fila['sucursal_acreditar'].
                $fila['ultimo_numero_primer_bloque'].
                $fila['numero_segundo_bloque'].
                $fila['clase_documento'] .
                $fila['importe_formateado'] .
                $fila['clase_documento'] .
                $fila['tipo_documento_beneficiario'] .
                $fila['importe'] .
                $fila['referencia'] .
                $fila['identificacion_cliente'] .
                $fila['cuil'] .
                $fila['cuil_con_ceros'] .
                $fila['uso_bna'] .
                $fila['datos_empresa'] .
                $fila['identificador_prestamo'] .
                $fila['nro_operacion_link'] . 
                $fila['sucursal_acreditar'] . 
                $fila['nro_registro'] . 
                $fila['observaciones'] . 
                $fila['filler'] . "\n";
        }

        // Define el nombre del archivo
        $nombreArchivo = 'datos_registro_tipo_2.txt';

        // Crea el archivo en el almacenamiento temporal
        file_put_contents($nombreArchivo, $contenido);

        // Proporciona una respuesta para descargar el archivo
        return response()->stream(
            function () use ($nombreArchivo) {
                readfile($nombreArchivo);
            },
            200,
            [
                'Content-Type' => 'text/plain',
                'Content-Disposition' => 'attachment; filename=' . $nombreArchivo,
            ]
        );
    }
}

    public function descargarDatosRegistroTipo3()
    {
        // Verifica que la sección actual sea "regitro_tipo1" y que haya datos antes de generar el archivo
        if ($this->seccionSeleccionada === 'registro_tipo_3' && !empty($this->ultimaFilaTipo3)) {
            // Formatea los campos de la última fila
        $ultimaFilaFormateada = sprintf(
            $this->ultimaFilaTipo3['tipo_registro'].
            $this->ultimaFilaTipo3['total_importe'].
            $this->ultimaFilaTipo3['total_registros'].
           $this->ultimaFilaTipo3['importe_aceptados'].
           $this->ultimaFilaTipo3['cantidad_registros_tipo2_aceptados'].
           $this->ultimaFilaTipo3['importes_rechazados'].
           $this->ultimaFilaTipo3['cantidad_registros_tipo2_rechazados'].
           $this->ultimaFilaTipo3['importe_comision']. 
           $this->ultimaFilaTipo3['importe_IVA'].            
           $this->ultimaFilaTipo3['importe_retencion_IVA']. 
           $this->ultimaFilaTipo3['importe_ingreso_bruto'].
           $this->ultimaFilaTipo3['importe_sellado_provincial'].
           $this->ultimaFilaTipo3['filler']
        );

            // Define el nombre del archivo
            $nombreArchivo = 'datos_registro_tipo_3.txt';

            // Crea el archivo en el almacenamiento temporal
            file_put_contents($nombreArchivo, $ultimaFilaFormateada);

            // Proporciona una respuesta para descargar el archivo
            return response()->stream(
                function () use ($nombreArchivo) {
                    readfile($nombreArchivo);
                },
                200,
                [
                    'Content-Type' => 'text/plain',
                    'Content-Disposition' => 'attachment; filename=' . $nombreArchivo,
                ]
            );
        }
    }

    public function cambiarSeccion($nuevaSeccion)
    {
        // Cambia la sección actual según la opción seleccionada
        $this->seccionSeleccionada = $nuevaSeccion;
    }

    public function siguientePagina()
    {
        $this->pagina++;
    }

    public function paginaAnterior()
    {
        $this->pagina--;
    }

    public function eliminarUltimosDatos()
{
    // Busca el último archivo de "Alta Proveedores" en la lista de registrosArchivos
    $ultimoIndice = $this->findLastIndexByTipoRegistro('Alta Proveedores');

    // Verifica si se encontró el último archivo
    if ($ultimoIndice !== null) {
        // Obtiene los datos del último archivo de "Alta Proveedores"
        $ultimosRegistros = $this->registrosArchivos[$ultimoIndice]['datos'];

        // Elimina los registros del último archivo de "Alta Proveedores" de la lista de datosAltaProveedor
        foreach ($ultimosRegistros as $registro) {
            $index = array_search($registro, $this->datosAltaProveedor);
            if ($index !== false) {
                unset($this->datosAltaProveedor[$index]);
            }
        }

        // Limpia los elementos eliminados
        $this->datosAltaProveedor = array_values($this->datosAltaProveedor);

        // Elimina el último archivo de "Alta Proveedores" de la lista de registrosArchivos
        unset($this->registrosArchivos[$ultimoIndice]);
        $this->registrosArchivos = array_values($this->registrosArchivos);

        // Realiza cualquier otra lógica necesaria después de eliminar los registros

        // Puedes agregar un mensaje de éxito o redireccionar según tus necesidades
    }
}

public function eliminarUltimoArchivoTipo1()
{
    // Busca el último archivo de "Registros Tipo 1" en la lista de registrosArchivos
    $ultimoIndice = $this->findLastIndexByTipoRegistro('Registros tipo 1'); // Asegúrate de pasar 'Registros tipo 1'

    // Verifica si se encontró el último archivo
    if ($ultimoIndice !== null) {
        // Obtiene los datos del último archivo de "Registros Tipo 1"
        $ultimosRegistros = $this->registrosArchivos[$ultimoIndice]['datos'];

        // Elimina los registros del último archivo de "Registros Tipo 1" de la lista de datosProcesadosTipo1
        foreach ($ultimosRegistros as $registro) {
            $index = array_search($registro, $this->datosProcesadosTipo1);
            if ($index !== false) {
                unset($this->datosProcesadosTipo1[$index]);
            }
        }

        // Limpia los elementos eliminados
        $this->datosProcesadosTipo1 = array_values($this->datosProcesadosTipo1);

        // Elimina el último archivo de "Registros Tipo 1" de la lista de registrosArchivos
        unset($this->registrosArchivos[$ultimoIndice]);
        $this->registrosArchivos = array_values($this->registrosArchivos);

        // Realiza cualquier otra lógica necesaria después de eliminar los registros

        // Puedes agregar un mensaje de éxito o redireccionar según tus necesidades
    }
}

public function eliminarUltimosDatosTipo2()
{
   // Busca el último archivo de "Registros Tipo 2" en la lista de registrosArchivos
   $ultimoIndice = $this->findLastIndexByTipoRegistro('Registros tipo 2'); // Asegúrate de pasar 'Registros tipo 2'

   // Verifica si se encontró el último archivo
   if ($ultimoIndice !== null) {
       // Obtiene el identificador único del último archivo de "Registros Tipo 2"
       $identificadorTipo2 = $this->registrosArchivos[$ultimoIndice]['identificador_tipo2'];

       // Recorre los datos de datosProcesadosTipo2 y elimina los que coincidan con el identificadorTipo2
       foreach ($this->datosProcesadosTipo2 as $index => $registro) {
           if ($registro['identificador_tipo2'] === $identificadorTipo2) {
               unset($this->datosProcesadosTipo2[$index]);
           }
       }

       // Reindexa los elementos
       $this->datosProcesadosTipo2 = array_values($this->datosProcesadosTipo2);

       // Elimina el último archivo de "Registros Tipo 2" de la lista de registrosArchivos
       unset($this->registrosArchivos[$ultimoIndice]);

       // Reindexa los elementos
       $this->registrosArchivos = array_values($this->registrosArchivos);

       // Elimina los datos tipo 3 procesados relacionados con el último archivo de "Registros Tipo 2"
       $this->registrosArchivos = array_filter($this->registrosArchivos, function ($archivo) use ($identificadorTipo2) {
           return $archivo['identificador_tipo2'] !== $identificadorTipo2;
       });

       // También elimina los datos tipo 3 procesados relacionados con el último archivo de "Registros Tipo 2"
       foreach ($this->datosProcesadosTipo3 as $index => $registroTipo3) {
           if ($registroTipo3['identificador_tipo2'] === $identificadorTipo2) {
               unset($this->datosProcesadosTipo3[$index]);
           }
       }

       // Reindexa los elementos
       $this->datosProcesadosTipo3 = array_values($this->datosProcesadosTipo3);

       // Realiza cualquier otra lógica necesaria después de eliminar los registros

       // Puedes agregar un mensaje de éxito o redireccionar según tus necesidades
   }
}

private function eliminarDatosTipo2YTipo3PorIdentificador($identificadorUnico)
{
    // Recorre los datos procesados de tipo 2 y tipo 3 y elimina los registros con el mismo identificador único
    foreach ($this->datosProcesadosTipo2 as $indexTipo2 => $registroTipo2) {
        if ($registroTipo2['identificador_unico'] === $identificadorUnico) {
            unset($this->datosProcesadosTipo2[$indexTipo2]);
        }
    }

    foreach ($this->datosProcesadosTipo3 as $indexTipo3 => $registroTipo3) {
        if ($registroTipo3['identificador_unico'] === $identificadorUnico) {
            unset($this->datosProcesadosTipo3[$indexTipo3]);
        }
    }

    // Reindexa los arrays después de eliminar registros
    $this->datosProcesadosTipo2 = array_values($this->datosProcesadosTipo2);
    $this->datosProcesadosTipo3 = array_values($this->datosProcesadosTipo3);
}

// Función auxiliar para encontrar el último índice de un tipo de registro específico
private function findLastIndexByTipoRegistro($tipoRegistro)
{
    $ultimoIndice = null;
    for ($i = count($this->registrosArchivos) - 1; $i >= 0; $i--) {
        if ($this->registrosArchivos[$i]['tipo_registro'] === $tipoRegistro) {
            $ultimoIndice = $i;
            break;
        }
    }
    return $ultimoIndice;
}

    public function render()
    {
        // Paginación manual de la colección según la sección actual
        $total = 0;
        $datosPaginados = [];
        $desde = ($this->pagina - 1) * $this->porPagina;
        $hasta = $desde + $this->porPagina;

        if ($this->seccionSeleccionada === 'alta_proveedor') {
            $total = count($this->datosAltaProveedor);
            $datosPaginados = array_slice($this->datosAltaProveedor, $desde, $this->porPagina);
        } elseif ($this->seccionSeleccionada === 'registro_tipo_1') {
            $total = count($this->datosProcesadosTipo1);
            $datosPaginados = array_slice($this->datosProcesadosTipo1, $desde, $this->porPagina);
        }elseif ($this->seccionSeleccionada === 'registro_tipo_2') {
            $total = count($this->datosProcesadosTipo2);
            $datosPaginados = array_slice($this->datosProcesadosTipo2, $desde, $this->porPagina);
        }elseif ($this->seccionSeleccionada === 'registro_tipo_3') {
            $total = count($this->datosProcesadosTipo3);
            $datosPaginados = array_slice($this->datosProcesadosTipo3, $desde, $this->porPagina);
        }

        return view('livewire.carga-archivo', [
            'datos' => $datosPaginados,
            'total' => $total,
            'desde' => $desde,
            'hasta' => $hasta,
            'seccionActual' => $this->seccionSeleccionada,
        ]);
    }
}