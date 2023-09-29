<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CargaArchivo extends Component
{
    use WithFileUploads;

    protected $listeners = ['datosTipo2Cargados' => 'cargaArchivoTipo3'];

    public $datosAltaProveedor = [];
    public $datosArchivoPago = [];
    public $datosProcesadosTipo1 = [];
    public $datosProcesadosTipo2 = [];
    public $datosProcesadosTipo3 = [];
    public $datosProcesados = [];
    public $datosParaTipo3 =[];

    public $ultimaFilaTipo3=[];
    public $totalImporteTipo2;
    public $contadorRegistrosTipo2 = 0;

    public $archivo;
    public $contenido;
    public $mostrarDatosAltaProveedor = false; // Agrega una propiedad para controlar la animación
    public $mostrarDatosArchivoPago = false;
    public $mostrarDatosTipo1 = false;
    public $mostrarDatosTipo2 = false;
    public $mostrarDatosTipo3 = false;
    public $cargandoDatosTipo1 = false;

    public $datos = []; // Array para almacenar los datos procesados
    public $porPagina = 6; // Número de elementos por página
    public $pagina = 1; // Página actual

    //secciones para el tipo de pago, predefinido el tipo 1
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
        $datos = $this->procesarArchivoExcel($this->archivo);
        }
    }

    public function procesarArchivoTipo1CSVoTXT($lineas){
        
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
            }
        }
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
        
            $titulares = '1'; // Valor fijo para Titulares
        
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
        }
    }
    // Establecer $mostrarDatos solo si se cargaron datos en la sección "Alta a Proveedores"
    if ($this->seccionSeleccionada === 'alta_proveedor') {
        $this->mostrarDatosAltaProveedor = true;
    }
}

    //carga de datos tipo 1
    public function cargaArchivoTipo1()
    {
        $this->validate([
            'archivo' => 'required|mimes:csv,txt,XLSX|max:2048',
        ]);
        
        // Obtener el contenido del archivo
        $contenido = file_get_contents($this->archivo->getRealPath());

        // Procesar el contenido del archivo CSV o TXT
        $lineas = explode("\n", $contenido);

        foreach ($lineas as $linea) {
            // Dividir la línea en elementos usando la coma como separador (para CSV)
            // O usar explode con tabulación "\t" si es un archivo de texto (TXT)
            $datos = str_getcsv($linea, ',');

            // Verificar si se obtuvieron datos válidos
            if (count($datos) >= 3) {
                // Tipo de Registro (Predefinido)
                $tipoRegistro = '1';

                // CUIT EMPRESA (Longitud 11)
                $cuitEmpresa = preg_replace('/[^0-9]/', '', $datos[0]);
                $cuitEmpresa = str_pad($cuitEmpresa, 11, '0', STR_PAD_LEFT);

                // CODIGO SUCURSAL (Longitud 4)
                $codigoSucursal = str_pad($datos[1], 4, '0', STR_PAD_LEFT);

                // CBU de la empresa (Longitud 11)
                $cbuEmpresa = preg_replace('/[^0-9]/', '', $datos[2]);
                $cbuEmpresa = str_pad($cbuEmpresa, 11, '0', STR_PAD_LEFT);

                // Dividir el DBU usando "-" como separador
                $dbu = explode('-', $datos[2]);
                if (count($dbu) == 2) {
                    $cbuDeseado = preg_replace('/[^0-9]/', '', $dbu[1]);
                    $cbuDeseado = str_pad($cbuDeseado, 11, '0', STR_PAD_LEFT);
                } else {
                    $cbuDeseado = ''; // Manejo de error si no hay un "-" en DBU
                }

                // Moneda (0 para pesos, 1 para dólares)
                $moneda = $datos[3];
                // Fecha de Pago (Longitud 8, formato aaaammdd)
                $fechaPago = substr($datos[4], 0, 8);

                // Informacion Criterio Empresa (Longitud 20, tipo alfanumerico)
                $infoCriterioEmpresa = substr($datos[5], 0, 20);

                // Tipo de Pago (Longitud 3, predefinido como "MIN")
                $tipoPago = 'MIN';

                // Clase de Pagos (Longitud 1, predefinido como "2")
                $clasePagos = '2';

                // Codigo de Convenio (Longitud 10, completar con ceros)
                $codigoConvenio = str_pad(preg_replace('/[^0-9]/', '', $datos[6]), 6, '0', STR_PAD_LEFT);

                // Numero de Envio (Longitud 6, completar con ceros)
                $numeroEnvio = str_pad(preg_replace('/[^0-9]/', '', $datos[7]), 6, '0', STR_PAD_LEFT);

                // Sistema Original (Longitud 2, completar con ceros)
                $sistemaOriginal = '00';

                // Filler (Longitud 15, completar con ceros)
                $filler = str_repeat('0', 15);

                // Casa Envio Rendicion (Longitud 4, completar con ceros)
                $casaEnvioRendicion = str_repeat('0', 4);

                // Filler2 (Longitud 100, completar con ceros)
                $filler2 = str_repeat('0', 100);

                if ($this->seccionSeleccionada === 'registro_tipo_1') {
                    // Agregar los datos procesados al array
                    $this->datosProcesadosTipo1[] = [
                        'tipo_registro' => $tipoRegistro,
                        'cuit_empresa' => $cuitEmpresa,
                        'codigo_sucursal' => $codigoSucursal,
                        'cbu_deseado' => $cbuDeseado,
                        'moneda' => $moneda,
                        'fecha_pago' => $fechaPago,
                        'info_criterio_empresa' => $infoCriterioEmpresa,
                        'tipo_pago' => $tipoPago,
                        'clase_pagos' => $clasePagos,
                        'codigo_convenio' => $codigoConvenio,
                        'numero_envio' => $numeroEnvio,
                        'sistema_original' => $sistemaOriginal,
                        'filler' => $filler,
                        'casa_envio_rendicion' => $casaEnvioRendicion,
                        'filler2' => $filler2,
                    ];
                }
            }
        }
        $this->mostrarDatosTipo1 = true;
    }

    public function cargaArchivoTipo2()
    {
        $this->validate([
            'archivo' => 'required|mimes:csv,txt,XLSX|max:2048',
        ]);

        // Obtener el contenido del archivo
        $contenido = file_get_contents($this->archivo->getRealPath());

        // Procesar el contenido del archivo CSV o TXT
        $lineas = explode("\n", $contenido);

        $totalImporte = 0;
        $importe = 0;

        $datosParaTipo3 = [];
        $contadorRegistrosTipo2= 0;

        foreach ($lineas as $linea) {
            // Dividir la línea en elementos usando la coma como separador (para CSV)
            // O usar explode con tabulación "\t" si es un archivo de texto (TXT)
            $datos = str_getcsv($linea, ',');

            // Verificar si se obtuvieron datos válidos
            if (count($datos) >= 3) {
                $tipoRegistro = '2';
                $entidad = str_pad($datos[0], 4, '0', STR_PAD_LEFT);
                $sucursal = str_pad($datos[1], 4, '0', STR_PAD_LEFT);
                $cbu = $datos[2];
                // Eliminar el carácter "-"
                // Dividir la cadena en función del guion "-"
                $bloques = explode("-", $cbu);

                // $bloques[0] contendrá el bloque 1 (número a la izquierda del "-")
                $bloque1 = substr($bloques[0], -1);

                // $bloques[1] contendrá el bloque 2 (resto del CBU)
                $bloque2 = $bloques[1];

               // Obtener el importe con comas y símbolo de dólar
                $importeConComas = $datos[3];

                // Utilizar una expresión regular para extraer el valor numérico
                if (preg_match('/\$([\d,.]+)/', $importeConComas, $matches)) {
                    // El valor numérico se encuentra en $matches[1]
                    $valorNumerico = str_replace([',', '$','.'], '', $matches[1]);

                    // Convertir el valor en un número entero
                    $importe = (int) $valorNumerico;
                }
                
                // Sumar el importe al total
                $totalImporte += $importe;
                
                // Obtener la referencia y completar con ceros solo si está vacía
                $referencia = empty($datos[4]) ? str_pad('', 15, '0') : $datos[4];

                // Identificación del cliente (con longitud fija de 22)
                // Se compone de un dígito 1(CUIT) O 2(CUIL) O 3(CDI) + número de clave fiscal
                // Eliminar caracteres especiales y espacios en blanco
                $identificacionCliente = preg_replace('/[^0-9a-zA-Z]/', '', $datos[5]);

                // Completar con espacios en blanco para llenar la longitud de 22
                $identificacionCliente = str_pad($identificacionCliente, 22, ' ');

                // clase de documento, se completa con 0 longitud 1
                $claseDocumento = "0";

                // tipo de documento, se completa con 00 longitud 2
                $tipoDocumento = "00";

                // documento del beneficiario, completar con 0 11 digitos
                $documentoBeneficiario = str_repeat('0', 11);

                $estado = "00";

                $datosEmpresa = empty($datos[6]) ? str_pad('', 13, '0') : $datos[6];

                $identificadorPrestamo = $datos[7];

                // numero operacion link uso BNA. longitud 9, completar con 0
                $operacionLink = str_repeat('0', 9);

                // uso BNA.
                $sucursalAcreditarBNA = str_repeat('0', 4);

                $numeroRegistroLink = str_repeat('0', 6);

                $observaciones = str_repeat('0', 15);

                $filler = str_repeat('0', 62);

                if ($this->seccionSeleccionada === 'registro_tipo_2') {
                    // Agregar los datos procesados al array
                    $this->datosProcesadosTipo2[] = [
                        'tipo_registro' => $tipoRegistro,
                        'entidad_acreditar' => $entidad,
                        'sucursal_acreditar' => $sucursal,
                        'digito_acreditar_bloque1' => $bloque1,
                        'digito_acreditar_cbu_bloque2' => $bloque2,
                        'importe' => $importe,
                        'referencia' => $referencia,
                        'identificacion_cliente' => $identificacionCliente,
                        'clase_documento' => $claseDocumento,
                        'tipo_documento' => $tipoDocumento,
                        'nro_documento' => $documentoBeneficiario,
                        'estado' => $estado,
                        'datos_empresa' => $datosEmpresa,
                        'identificador_prestamo' => $identificadorPrestamo,
                        'nro_operacion_link' => $operacionLink,
                        'sucursal_acreditar_BNA' => $sucursalAcreditarBNA,
                        'numero_registro_link' => $numeroRegistroLink,
                        'observaciones' => $observaciones,
                        'filler' => $filler,
                    ];

                        // Agregar los datos necesarios para Tipo 3 al arreglo
                    $this->datosParaTipo3[] = [
                        'total_importe' => $totalImporte,
                        'total_registros' => $contadorRegistrosTipo2,
                        // ... (otros campos para Tipo 3)
                    ]; 
                    
                }
            }
        }
        // Guardar el total de importe para su uso posterior
        $this->totalImporteTipo2 = $totalImporte;
        $this->mostrarDatosTipo2 = true;

            // Asignar los datos a la propiedad $datosParaTipo3 antes de emitir el evento
        $this->datosParaTipo3 = $datosParaTipo3;

        // Emitir un evento con los datos para cargaArchivoTipo3
        $this->emit('datosTipo2Cargados', $this->totalImporteTipo2, $contadorRegistrosTipo2);
    }
    
    public function cargaArchivoTipo3()
    {
            // Verificar si se obtuvieron datos válidos
                $tipoRegistro = "3";

                // Obtener los datos acumulados de cargaArchivoTipo2
                $datosTipo2 = $this->datosProcesadosTipo2;

                $totalImporteTipo2 = 0;
                $totalRegistrosTipo2 = 0;

                // Calcular el total de importe y registros de cargaArchivoTipo2
                foreach ($datosTipo2 as $dato) {
                    $totalImporteTipo2 += $dato['importe'];
                    // Formatear $totalImporteTipo2 como cantidad de dinero
                    $totalImporteTipo2Formateado = number_format($totalImporteTipo2, 2, '.', '');
                    $totalImporteTipo2Formateado = str_replace('.', '', $totalImporteTipo2Formateado); // Eliminar el punto
                    $totalImporteTipo2Formateado = str_pad($totalImporteTipo2Formateado, 15, '0', STR_PAD_LEFT); // Rellenar con ceros
                    $totalRegistrosTipo2++;
                    $totalRegistrosTipo2 = str_pad($totalRegistrosTipo2, 7, '0', STR_PAD_LEFT);
                }

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

                    $this->ultimaFilaTipo3 = [
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
            // Puedes mostrar los datos en tu vista, estableciendo una variable de bandera, por ejemplo:
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
        // Verifica que la sección actual sea "regitro_tipo1" y que haya datos antes de generar el archivo
        if ($this->seccionSeleccionada === 'registro_tipo_2' && count($this->datosProcesadosTipo2) > 0) {
            // Genera el contenido del archivo TXT
            $contenido = '';
            foreach ($this->datosProcesadosTipo2 as $fila) {
                // Formatea los campos según las longitudes
                $contenido .=
                    $fila['tipo_registro'] .
                    $fila['entidad_acreditar'] .
                    $fila['sucursal_acreditar'] .
                    $fila['digito_acreditar_bloque1'] .
                    $fila['digito_acreditar_cbu_bloque2'] .
                    $fila['importe'] .
                    $fila['referencia'] .
                    $fila['identificacion_cliente'] .
                    $fila['clase_documento'] .
                    $fila['tipo_documento'] .
                    $fila['nro_documento'] .
                    $fila['estado'] .
                    $fila['datos_empresa'] .
                    $fila['identificador_prestamo'] .
                    $fila['nro_operacion_link'] .
                    $fila['sucursal_acreditar_BNA'] .
                    $fila['numero_registro_link'] .
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
