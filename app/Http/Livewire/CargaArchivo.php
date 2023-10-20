<?php
//Componente donde se ecuentra toda la logica de el programa, tanto la carga, descarga y eliminacion de archivos.

namespace App\Http\Livewire;

use ReflectionClass;
use App\Helpers\Expressions;
use DateTime;
use Illuminate\Database\Query\Expression;
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
    public $popupMessage = '';
    public $errores = [];
    public $datosNoEncontrados = [];
    public $datosFaltantes = [];
    public $mostrarMensajeDatosFaltantes = false;
    public $mensajeError = '';
    public $mostrarMensajeError = false;
    public $intentoDescarga = false;
    public $datosFaltantesTipo2 = [];
    public $datosFaltantesTipo1 = [];
    public $mostrarMensajeErrorTipo1 = false;
    public $mostrarMensajeErrorTipo2 = false;
    public $mostrarMensajeErrorAltaProveedores = false;
    public $datosNoEncontradosAltaProveedor = [];
    public $mostrarDatosFaltantesTipo1 = [];
    public $popupMessageAltaProveedor = [];

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

    

public function cargaArchivoTipo1()
{
    $this->validate([
        'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
    ]);

    $datosNoEncontrados = [];
    $datosArchivoActual = [];

    $contenido = file_get_contents($this->archivo->getRealPath());
    $lineas = explode("\n", $contenido);

    $contadorRegistrosTipo1 = 0;
    $contadorLinea = 0;

    foreach ($lineas as $linea) {
        // Incrementa el contador de línea
        $contadorLinea++;

        // Dividir la línea en elementos usando el punto y coma como separador
        $datos = str_getcsv($linea, ';');

        // Inicializa datos preestablecidos con ceros
        $datosPreestablecidos = [
            'tipo_pagos' => 'MIN',
            'clase_pagos' => '2',
            'sistema_original' => str_repeat(' ',2),
            'filler' => str_repeat(' ', 15),
            'casa_envio_rendicion' => str_repeat(' ', 4),
            'filler_100' => str_repeat(' ', 100),
        ];

        $datosValidados = [
            'tipo_registro' => '1',
        ];

        $cbuEncontrado = false;
        $cuitEncontrado = false;
        $monedaEncontrada = false;
        $cuentaSucursalEncontrada = false;
        $fechaPagoEncontrada = false;
        $infoCriterioEmpresaEncontrada = false;
        $codigoConvenioEncontrado = false;
        $numeroEnvioEncontrado = false;

        $camposFaltantes = [];

        // Inicializa los contadores
        $contadorMoneda = 0;
        $contadorNumeroEnvio = 0;

        foreach ($datos as $dato) {
            if ($this->validarCBU($dato)) {
                $datosValidados['cbu'] = $dato;
                $cbuEncontrado = true;
                $cuentaSucursalEncontrada = true;
                // Divide el CBU en entidad y sucursal
                $entidad = substr($dato, 4, 3);
                $datosValidados['entidad_acreditar'] = $entidad;
            } elseif ($this->validarCUIT($dato)) {
                $datosValidados['cuit'] = $dato;
                $cuitEncontrado = true;
            }if ($monedaEncontrada === false && ($dato === '0' || $dato === '1')) {
                $datosValidados['moneda'] = $dato;
                $monedaEncontrada = true;
            } elseif ($numeroEnvioEncontrado === false && ($dato === '1' || $dato === '2')) {
                $datosValidados['numero_envio'] = $dato;
                $numeroEnvioEncontrado = true;
            } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dato)) {
                $datosValidados['fecha_pago'] = $dato;
                $fechaPagoEncontrada = true;
            } elseif (preg_match('/^\d{4}$|^\d{8}$/', $dato)) {
                // Si $dato contiene solo números, asignarlo a $codConvenio
                $datosValidados['codigo_convenio'] = $dato;
                $codigoConvenioEncontrado = true;
            } elseif (preg_match('/^[a-zA-Z\s]+$/', $dato)) {
                // Si $dato contiene solo letras y espacios, asignarlo a $infoCriterioEmpresa
                $datosValidados['info_criterio_empresa'] = $dato;
                $infoCriterioEmpresaEncontrada = true;
            } elseif (preg_match('/^[12]$/', $dato)) {
                if ($contadorNumeroEnvio == 0) {
                    $datosValidados['numero_envio'] = $dato;
                    $numeroEnvioEncontrado = true;
                    $contadorNumeroEnvio++;
                }
            }
        }

        // Agrega los datos procesados solo si todos los campos requeridos están presentes
        if (!empty($datosValidados)){
            $datosArchivoActual[] = $datosValidados;

            if (!$cbuEncontrado) {
                $camposFaltantes[] = "CBU";
            }

            if (!$cuitEncontrado) {
                $camposFaltantes[] = "CUIT";
            }

            if (!$monedaEncontrada) {
                $camposFaltantes[] = "Moneda";
            }

            if (!$fechaPagoEncontrada) {
                $camposFaltantes[] = "Fecha de Pago";
            }

            if (!$infoCriterioEmpresaEncontrada) {
                $camposFaltantes[] = "Información de Criterio de Empresa";
            }

            if (!$codigoConvenioEncontrado) {
                $camposFaltantes[] = "Código de Convenio";
            }

            if (!$numeroEnvioEncontrado) {
                $camposFaltantes[] = "Número de Envío";
            }
            
            if(!empty($camposFaltantes)){
                $datosNoEncontrados[$contadorLinea] = $camposFaltantes;
            }
        }
                $contadorRegistrosTipo1++;
            }

            $this->datosProcesadosTipo1 = array_merge($this->datosProcesadosTipo1, $datosArchivoActual);

            if(isset($datosNoEncontrados)){
                $this->datosFaltantesTipo1 = $datosNoEncontrados;
                $this->datosNoEncontradosTipo1($this->datosFaltantesTipo1);
            }

            $this->registrosArchivos[] = [
                'nombre_archivo' => $this->archivo->getClientOriginalName(),
                'tipo_registro' => 'Registros tipo 1',
                'datos' => $datosArchivoActual,
            ];

            $this->mostrarDatosTipo1 = true;

            $this->emit('datosTipo1Cargados', count($datosArchivoActual));

            $this->datosNoEncontrados = $datosNoEncontrados;

        }

        public function datosNoEncontradosTipo1($datosfaltantes){
            if (!empty($datosfaltantes)) {
                $this->popupMessage = 'Datos no encontrados:<br>';
            
                foreach ($datosfaltantes as $linea => $camposFaltantes) {
                    $this->popupMessage .= 'Línea ' . $linea . ': ' . implode(', ', $camposFaltantes) . '<br>';
                }
            }
        }

        

            public function noEncontradosTipo2($datosFaltantes){
                if (!empty($datosFaltantes)) {
                $this->popupMessage = 'Datos no encontrados:<br>';

                foreach ($datosFaltantes as $linea => $camposFaltantes) {
                    $camposFaltantesUnicos = array_unique($camposFaltantes);
                    $this->popupMessage .= 'Línea ' . $linea . ': ' . implode(', ', $camposFaltantesUnicos) . '<br>';
                }
            }
        }

        public function mostrarVistaRegistroTipo2()
        {
            // Puedes configurar cualquier variable que necesites aquí
            $this->seccionSeleccionada = 'registro_tipo_2';

            // Redirige a la vista "registro-tipo2.blade.php"
            return redirect()->to(route('nombre_de_la_ruta_registro_tipo2'));
        }

private function validarEntidad($dato)
{
    // Realiza la validación para entidad aquí, devuelve true si es válido, false en caso contrario
    return preg_match(Expressions::$expresionEntidad, $dato);
}

private function validarCuentaSucursal($dato)
{
    // Realiza la validación para CuentaSucursal aquí, devuelve true si es válido, false en caso contrario
    return preg_match(Expressions::$expresionCuentaSucursal, $dato);
}

private function validarCBU($dato)
{
    // Realiza la validación para CBU aquí, devuelve true si es válido, false en caso contrario
    return preg_match(Expressions::$expresionCBU, $dato);
}

private function validarCUIT($dato)
{
    // Realiza la validación para CUIT aquí, devuelve true si es válido, false en caso contrario
    return preg_match(Expressions::$expresionCUIT, $dato);
}

private function validarImporte($dato)
{
    // Realiza la validación para Importe aquí, devuelve true si es válido, false en caso contrario
    return preg_match('/-?\d+,\d{2}/', $dato);
}

private function validarReferencia($dato)
{
    // Realiza la validación para Referencia aquí, devuelve true si es válido, false en caso contrario
    return preg_match(Expressions::$expresionReferencia, $dato);
}

private function validarIdentificacionCliente($dato)
{
    // Realiza la validación para Referencia aquí, devuelve true si es válido, false en caso contrario
    return preg_match(Expressions::$expresionIdentificacionCliente, $dato);
}

    public function closePopup()
{
    $this->popupMessage = '';
    $this->mensajeError = '';
    $this->mostrarMensajeErrorTipo1 = false;
    $this->mostrarMensajeErrorTipo2 = false;
    $this->mostrarMensajeErrorAltaProveedores = false;
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
                    if (array_key_exists('importe', $dato)) {
                        // Eliminar caracteres no numéricos y convertir a número entero
                        $importeEntero = intval(str_replace(['$', ',', '.'], '', $dato['importe']));
                
                        // Sumar el importe entero al total
                        $totalImporteTipo2 += $importeEntero;
                
                        // Formatear $totalImporteTipo2 como cantidad de dinero si es necesario
                        $totalImporteTipo2Formateado = number_format($totalImporteTipo2, 2, '.', '');
                        $totalImporteTipo2Formateado = str_replace('.', '', $totalImporteTipo2Formateado); // Eliminar el punto
                        $totalImporteTipo2Formateado = str_pad($totalImporteTipo2Formateado, 15, '0', STR_PAD_LEFT); // Rellenar con ceros
                        $totalRegistrosTipo2 = str_pad($totalRegistrosTipo2, 7, '0', STR_PAD_LEFT);
                        $totalRegistrosTipo2++;
                    }
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

                // Buscar el índice de la fila existente en $datosProcesadosTipo3
                $indiceFilaExistente = null;
                foreach ($this->datosProcesadosTipo3 as $indice => $fila) {
                    if ($fila['identificador_tipo2'] === $identificadorTipo2) {
                        $indiceFilaExistente = $indice;
                        break;
                    }
                }

                if ($indiceFilaExistente !== null) {
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
                }else{
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
                }

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

   

public function datosNoEncontrados(){
    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo! Los siguientes campos son obligatorios: ';
    $this->mostrarMensajeError = true;
    $this->mostrarMensajeErrorAltaProveedores = true;
    $this->intentoDescarga = true;
    return;
}
    
    public function descargarDatosRegistroTipo1()
    {
        // Verifica que la sección actual sea "registro_tipo_1" y que haya datos antes de generar el archivo
        if ($this->seccionSeleccionada === 'registro_tipo_1' && count($this->datosProcesadosTipo1) > 0) {
            // Verifica que todos los campos necesarios estén presentes en al menos una fila
            $camposNecesarios = [
                'cuit',
                'entidad_acreditar',
                'cbu',
                'moneda',
                'fecha_pago',
                'info_criterio_empresa',
                'clase_pagos',
                'codigo_convenio',
                'numero_envio',
            ];
    
            $datosFaltantes = [];
    
            foreach ($camposNecesarios as $campo) {
                $campoEncontrado = false;
    
                foreach ($this->datosProcesadosTipo1 as $fila) {
                    if (isset($fila[$campo])) {
                        $campoEncontrado = true;
                        break;
                    }
                }
    
                if (!$campoEncontrado) {
                    $datosFaltantes[] = $campo;
                }
            }

            if (!empty($datosFaltantes)) {
                // Al menos un campo necesario está faltante
                $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                $this->mostrarMensajeError = true;
                $this->mostrarMensajeErrorTipo1 = true;
                $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;
    
                // Establece el intento de descarga
                $this->intentoDescarga = true;
    
                // Retorna para no continuar con la descarga
                return;
            }
    
            // Genera el contenido del archivo TXT
            $contenido = '';
            foreach ($this->datosProcesadosTipo1 as $fila) {
                if(isset($fila['entidad_acreditar'])){
                   $cuentaSuc = $fila['entidad_acreditar']; 
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                $this->mostrarMensajeError = true;
                $this->mostrarMensajeErrorTipo1 = true;
                $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;
    
                // Establece el intento de descarga
                $this->intentoDescarga = true;
    
                // Retorna para no continuar con la descarga
                return;
                }
                // Verificar si la cadena tiene 3 caracteres numéricos
                if (strlen($cuentaSuc) === 3 && is_numeric($cuentaSuc)) {
                    // Agregar ceros a la izquierda para que la longitud sea 4
                    $cuentaSuc = str_pad($cuentaSuc, 4, '0', STR_PAD_LEFT);
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                $this->mostrarMensajeError = true;
                $this->mostrarMensajeErrorTipo1 = true;
                $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;
    
                // Establece el intento de descarga
                $this->intentoDescarga = true;
    
                // Retorna para no continuar con la descarga
                return;
                }

                if(isset($fila['cbu'])){
                $cbu = $fila['cbu'];
                $primerBloque = substr($cbu, 0, 8);

                // Obtener los siguientes 14 dígitos
                $segundoBloque = substr($cbu, 8, 14);
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                $this->mostrarMensajeError = true;
                $this->mostrarMensajeErrorTipo1 = true;
                $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;
    
                // Establece el intento de descarga
                $this->intentoDescarga = true;
    
                // Retorna para no continuar con la descarga
                return;
                }

                if(isset($fila['fecha_pago'])){
                   $fechaPago = $fila['fecha_pago'];
                   $fechaSinBarras = str_replace('/', '', $fechaPago);
   
                   // Utiliza DateTime para convertir la fecha
                   $fecha = DateTime::createFromFormat('dmY', $fechaSinBarras);
   
                   // Formatea la fecha como deseas
                   $fechaFormateada = $fecha->format('Ymd');
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                $this->mostrarMensajeError = true;
                $this->mostrarMensajeErrorTipo1 = true;
                $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;
    
                // Establece el intento de descarga
                $this->intentoDescarga = true;
    
                // Retorna para no continuar con la descarga
                return;
                }
               
                if(isset($fila['codigo_convenio'])){
                    $codConvenio = $fila['codigo_convenio'];
                    $longitudActual = strlen($codConvenio);
    
                    // Define la longitud objetivo (10 caracteres en total)
                    $longitudObjetivo = 10;
    
                    // Calcula cuántos ceros agregar a la izquierda y a la derecha
                    if ($longitudActual == 4) {
                        // Si tiene 4 dígitos, agrega 2 ceros a la izquierda y 4 a la derecha
                        $cerosAIzquierda = 2;
                        $cerosADerecha = 4;
                    } elseif ($longitudActual == 8) {
                        // Si tiene 8 dígitos, agrega 2 ceros a la izquierda y ninguno a la derecha
                        $cerosAIzquierda = 2;
                        $cerosADerecha = 0;
                    }
                    // Verifica la longitud actual del número (4 caracteres)
    
                    // Agrega los ceros necesarios a la izquierda y a la derecha
                    $codConvenioFormateado = str_repeat('0', $cerosAIzquierda) . $codConvenio . str_repeat('0', $cerosADerecha);
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                $this->mostrarMensajeError = true;
                $this->mostrarMensajeErrorTipo1 = true;
                $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;
    
                // Establece el intento de descarga
                $this->intentoDescarga = true;
    
                // Retorna para no continuar con la descarga
                return;
                }
            
                if(isset($fila['numero_envio'])){
                    $numeroEnvio = $fila['numero_envio'];
                    $numeroEnvioFormateado = str_pad($numeroEnvio, 6, '0', STR_PAD_LEFT);
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                $this->mostrarMensajeError = true;
                $this->mostrarMensajeErrorTipo1 = true;
                $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;
    
                // Establece el intento de descarga
                $this->intentoDescarga = true;
    
                // Retorna para no continuar con la descarga
                return;
                }

                // Formatea los campos según las longitudes
                $contenido .=
                    $fila['tipo_registro'] .
                    $fila['cuit'] .
                    $cuentaSuc .
                    $segundoBloque .
                    $fila['moneda'] .
                    $fechaFormateada .
                    $fila['info_criterio_empresa'] .
                    $fila['tipo_pagos'] .
                    $fila['clase_pagos'] .
                    $codConvenioFormateado .
                    $numeroEnvioFormateado .
                    $fila['sistema_original'] .
                    $fila['filler'] .
                    $fila['casa_envio_rendicion'] .
                    $fila['filler_100'] . "\n";
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
    
    
    

public function descargarDatosRegistroTipo3()
{
    // Verifica que la sección actual sea "registro_tipo_3" y que haya datos antes de generar el archivo
    if ($this->seccionSeleccionada === 'registro_tipo_3' && !empty($this->ultimaFilaTipo3)) {
        // Obtiene la última fila de datos
        $ultimaFila = end($this->ultimaFilaTipo3);

        // Formatea los campos de la última fila
        $ultimaFilaFormateada = sprintf(
            $ultimaFila['tipo_registro'] .
            $ultimaFila['total_importe'] .
            $ultimaFila['total_registros'] .
            $ultimaFila['importe_aceptados'] .
            $ultimaFila['cantidad_registros_tipo2_aceptados'] .
            $ultimaFila['importes_rechazados'] .
            $ultimaFila['cantidad_registros_tipo2_rechazados'] .
            $ultimaFila['importe_comision'] .
            $ultimaFila['importe_IVA'] .
            $ultimaFila['importe_retencion_IVA'] .
            $ultimaFila['importe_ingreso_bruto'] .
            $ultimaFila['importe_sellado_provincial'] .
            $ultimaFila['filler']
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

        if ($this->seccionSeleccionada === 'registro_tipo_2') {
            return view('livewire.registros-tipo2', [
                'mostrarDatosTipo2' => true,
                'datos' => $datosPaginados,
                'total' => $total,
                'desde' => $desde,
                'hasta' => $hasta,
                'seccionActual' => $this->seccionSeleccionada,
            ]);
        } else {

        return view('livewire.carga-archivo', [
            'datos' => $datosPaginados,
            'total' => $total,
            'desde' => $desde,
            'hasta' => $hasta,
            'seccionActual' => $this->seccionSeleccionada,
        ]);
    }
    }
}