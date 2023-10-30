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
use App\Jobs\ProcesarArchivoJob;
use App\Listeners\ProcesarDatosProcesados;
use App\Events\DatosProcesados;
use Illuminate\Queue\Listener;
use Carbon\Carbon;

class CargaArchivo extends Component
{
    use WithFileUploads;

    protected $listeners = [
        'datosTipo2Cargados' => 'cargaArchivoTipo3',
        'archivoCargadoTipo1' => 'cargaArchivoTipo1',
        'archivoCargadoTipo2' => 'cargaArchivoTipo2',
    ];
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
    public $cargando = false;
    public $datosArchivoActual = [];
    public $archivoCargado;
    public $identificadorUnico;
    public $params;

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
    public $seccionSeleccionada = "registro_tipo_2";

    public function procesarArchivosAltaProveedores()
    {
        $this->validate([
            'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
        ]);
    
        $datosNoEncontrados = [];
        $datosArchivoActual = [];
    
        $contenido = file_get_contents($this->archivo->getRealPath());
        $lineas = explode("\n", $contenido);
        $identificadorUnico = uniqid();

        if (isset($lineas[0])) {
            $encabezado = $lineas[0];
            unset($lineas[0]);
        }

        $contadorRegistrosAltaProveedor = 0;
    
        for ($contadorLinea = 1; $contadorLinea < count($lineas); $contadorLinea++) {
            $linea = $lineas[$contadorLinea];
    
            // Dividir la línea en elementos usando el punto y coma como separador
            $datos = str_getcsv($linea, ';');
    
            // Inicializa variables para verificar si se han encontrado los campos requeridos
            $cbuEncontrado = false;
            $aliasEncontrado = false;
            $idTipoEncontrado = false;
            $cuitEncontrado = false;
            $tipoCuentaEncontrado = false;
            $referenciaEncontrada = false;
            $emailEncontrado = false;
    
            // Arreglo para los datos validados
            $datosValidados = [];
            $camposFaltantes = []; 

            $datosValidados = [
                'titulares' => '1',
            ];

            foreach ($datos as $key => $dato) {
                // Realiza la validación específica para cada tipo de dato
                // CBU
                if ($this->validarCBU($dato)) {
                    $datosValidados['cbu'] = $dato;
                    $cbuEncontrado = true;
                } elseif ($dato === '1' || $dato === '2'|| $dato === '3') { // Id Tipo Clave
                    $datosValidados['id_tipo'] = $dato;
                    $idTipoEncontrado = true;
                } elseif (in_array($dato, ['01', '02', '03', '04', '05', '06', '07'])) { // Tipo de Cuenta
                    $datosValidados['tipo_cuenta'] = $dato;
                    $tipoCuentaEncontrado = true;
                } elseif ($this->validarCUIT($dato)) { // CUIT
                    $datosValidados['cuit'] = $dato;
                    $cuitEncontrado = true;
                }elseif (preg_match('/^[A-Za-z0-9.]{0,22}$/', $dato)) { // Alias
                    $alias = substr($dato, 0, 22);
                    $alias = str_repeat('0', 22);
                    $datosValidados['alias'] = $alias;
                    $aliasEncontrado = true;
                }elseif (preg_match('/^[A-Za-z0-9\s]{0,30}$/', $dato)) { // Referencia
                    $referencia = substr($dato, 0, 30);
                    $referencia = str_pad($referencia, 30, ' ', STR_PAD_RIGHT);
                    $datosValidados['referencia'] = $referencia;
                    $referenciaEncontrada = true;
                } elseif (preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $dato)) { // Email
                    $email = substr($dato, 0, 50);
                    $email = str_pad($email, 50, ' ', STR_PAD_RIGHT);
                    $datosValidados['email'] = $email;
                    $emailEncontrado = true;
                }

                if (!$emailEncontrado) {
                    $datosValidados['email'] = str_repeat(' ', 50);
                }

                if (!$referenciaEncontrada) {
                    $datosValidados['referencia'] = str_repeat(' ', 30);
                }
            }
                
            // Verifica si todos los campos requeridos se encontraron
            if (!empty($datosValidados)){
                $datosArchivoActual[] = $datosValidados;

                if (!$cbuEncontrado) {
                    $camposFaltantes[] = "CBU";
                }
                if (!$aliasEncontrado) {
                    $camposFaltantes[] = "Alias";
                }
                if (!$idTipoEncontrado) {
                    $camposFaltantes[] = "Id Tipo Clave";
                }
                if (!$cuitEncontrado) {
                    $camposFaltantes[] = "CUIT";
                }
                if (!$tipoCuentaEncontrado) {
                    $camposFaltantes[] = "Tipo de Cuenta";
                }
                if(!empty($camposFaltantes)){
                    $datosNoEncontrados[$contadorLinea] = $camposFaltantes;
                }
            }
            $contadorRegistrosAltaProveedor++;
        }
        $this->datosAltaProveedor = array_merge($this->datosAltaProveedor, $datosArchivoActual);
        $this->datosNoEncontradosAltaProveedor = $datosNoEncontrados;
    
        $this->registrosArchivos[] = [
            'nombre_archivo' => $this->archivo->getClientOriginalName(),
            'tipo_registro' => 'Alta Proveedores',
            'datos' => $datosArchivoActual,
        ];
    
        $this->mostrarDatosAltaProveedor = true;
    
        $this->emit('datosAltaProveedorCargados', count($datosArchivoActual));

        $this->archivoCargado = $this->archivo;

        $this->identificadorUnico = $identificadorUnico;

        $this->emit('archivoCargadoTipo1', ['archivo' => $this->archivo, 'id' => $identificadorUnico]);
        $this->emit('archivoCargadoTipo2', ['archivo' => $this->archivo, 'id' => $identificadorUnico]);

    
        if (!empty($datosNoEncontrados)) {
            $this->popupMessage = 'Datos no encontrados:<br>';
        
            foreach ($datosNoEncontrados as $linea => $camposFaltantes) {
                $this->popupMessage .= 'Línea ' . $linea . ': ' . implode(', ', $camposFaltantes) . '<br>';
            }
        }
    }    

public function cargaArchivoTipo1($params = null)
{
    $this->validate([
        'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
    ]);

    $datosNoEncontrados = [];
    $datosArchivoActual = [];

    $contenido = file_get_contents($this->archivo->getRealPath());
    // Verifica si $params está presente
    if (!empty($params) && isset($params['id'])) {
        $identificadorUnico = $params['id'];
    } else {
        $identificadorUnico = null; // Otra acción si no se proporciona $params
    }
    $lineas = explode("\n", $contenido);

    if (isset($lineas[0])) {
        $encabezado = $lineas[0];
        unset($lineas[0]);
    }

    $contadorRegistrosTipo1 = 0;

    for ($contadorLinea = 1; $contadorLinea < count($lineas); $contadorLinea++) {
        $linea = $lineas[$contadorLinea];
        // Incrementa el contador de línea

        // Dividir la línea en elementos usando el punto y coma como separador
        $datos = str_getcsv($linea, ';');

        // Establecer la localización a español
        Carbon::setLocale('es');

        // Array de traducción de nombres de meses
        $mesesEnEspanol = [
            'January' => 'ENERO',
            'February' => 'FEBRERO',
            'March' => 'MARZO',
            'April' => 'ABRIL',
            'May' => 'MAYO',
            'June' => 'JUNIO',
            'July' => 'JULIO',
            'August' => 'AGOSTO',
            'September' => 'SEPTIEMBRE',
            'October' => 'OCTUBRE',
            'November' => 'NOVIEMBRE',
            'December' => 'DICIEMBRE',
        ];

        // Obtener el nombre del mes actual en mayúsculas
        $nombreMesActual = strtoupper($mesesEnEspanol[Carbon::now()->format('F')]);

        // Crear la cadena 'info_criterio_empresa' con un máximo de 20 caracteres
        $infoCriterioEmpresa = 'PAGO PROVEED ' . substr($nombreMesActual, 0, 9); // Recortar a 9 caracteres si es necesario

        // Asegurarse de que 'info_criterio_empresa' tenga una longitud máxima de 20 caracteres
        if (strlen($infoCriterioEmpresa) > 20) {
            $infoCriterioEmpresa = substr($infoCriterioEmpresa, 0, 20);
        }

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
            'moneda'=> '0',
            'fecha_pago' => date("d/m/Y"),
            'info_criterio_empresa' => $infoCriterioEmpresa,
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
                // Divide el CBU en entidad y sucursal
                $entidad = substr($dato, 4, 3);
                $datosValidados['entidad_acreditar'] = $entidad;
            } elseif ($this->validarCUIT($dato)) {
                $datosValidados['cuit'] = $dato;
                $cuitEncontrado = true;
            } elseif ($numeroEnvioEncontrado === false && ($dato === '1' || $dato === '2')) {
                $datosValidados['numero_envio'] = $dato;
                $numeroEnvioEncontrado = true;
            } elseif (preg_match('/^\d{4}$|^\d{8}$/', $dato)) {
                // Si $dato contiene solo números, asignarlo a $codConvenio
                $datosValidados['codigo_convenio'] = $dato;
                $codigoConvenioEncontrado = true;
            } elseif (preg_match('/^[12]$/', $dato)) {
                if ($contadorNumeroEnvio == 0) {
                    $datosValidados['numero_envio'] = $dato;
                    $numeroEnvioEncontrado = true;
                    $contadorNumeroEnvio++;
                }
            }
            // Verifica si $identificadorUnico está presente
            if (is_null($identificadorUnico) && !empty($params) && isset($params['id'])) {
                $datosValidados['identificador_alta_proveedores'] = $identificadorUnico;
            } elseif (is_null($identificadorUnico)) {
                $datosValidados['identificador_alta_proveedores'] = null;
            }
    }
        $datosValidados += $datosPreestablecidos;


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

        public function cargaArchivoTipo2($params = null)
        {
            $this->validate([
                'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
            ]);
            $this->cargando = true;

            // Ahora puedes cargar el contenido del archivo desde la ruta
            $contenido = file_get_contents($this->archivo->getRealPath());

            if (!empty($params) && isset($params['id'])) {
                $identificadorUnico = $params['id'];
            } else {
                $identificadorUnico = null; // Otra acción si no se proporciona $params
            }
        
            $datosNoEncontrados = [];
            $datosArchivoActual = [];

            $lineas = explode("\n", $contenido);

            if (isset($lineas[0])) {
                $encabezado = $lineas[0];
                unset($lineas[0]);
            }

            $identificadorTipo2 = uniqid();
            $this->identificadorTipo2 = $identificadorTipo2;

            $contadorRegistrosTipo2 = 0;

            // Comienza a leer desde la segunda línea (índice 1)
            for ($contadorLinea = 1; $contadorLinea < count($lineas); $contadorLinea++) {
                $linea = $lineas[$contadorLinea];
                
            // Dividir la línea en elementos usando el punto y coma como separador
            $datos = str_getcsv($linea, ';');
        
            // Inicializa datos preestablecidos con ceros
            $datosPreestablecidos = [
                'identificacion_cliente'=> '1',
                'clase_documento' => '0',
                'tipo_documento' => '00',
                'uso_BNA'=> '00',
                'nro_documento' => str_repeat('0',11),
                'estado' => '00',
                'datos_de_la_empresa' => str_repeat(' ',13),
                'cuil_con_ceros'=> str_repeat('0',11),
                'identificador_prestamo' => '0000',
                'nro_operacion_link' => str_repeat( ' ',9),
                'sucursal' => str_repeat(' ', 4),
                'numero_registro_link' => str_repeat(' ',6),
                'observaciones' => str_repeat(' ',15),
                'filler' => str_repeat(' ',62),
            ];
        
            $datosValidados = [
                'tipo_registro' => '2',
                'identificador_tipo2' => $identificadorTipo2,
            ];
        
            $cbuEncontrado = false; // Variable para verificar si se encontró CBU en esta línea
            $entidadEncontrada = false;
            $cuentaSucursalEncontrada = false;
            $cuitEncontrado = false;
            $importeEncontrado = false;
            $referenciaEncontrada = false;
            $identificacionClienteEncontrada = false;
            $camposFaltantes = [];
        
            foreach ($datos as $key => $dato) {
                // Realiza la validación específica para cada tipo de dato
                if ($this->validarCBU($dato)) {
                    $datosValidados['cbu'] = $dato; 
                    $cbuEncontrado = true;
                    $entidadEncontrada = true;
                    $cuentaSucursalEncontrada = true;
                    // Divide el CBU en entidad y sucursal
                    $entidad = substr($dato, 0, 3);
                    $sucursal = substr($dato, 4, 3);
                    $datosValidados['entidad_acreditar'] = $entidad;
                    $datosValidados['sucursal_acreditar'] = $sucursal;
                } elseif ($this->validarCUIT($dato)) {
                    $datosValidados['cuit'] = $dato;
                    $cuitEncontrado = true;
                } elseif (!$importeEncontrado && $this->validarImporte($dato)) {
                    $importe = preg_replace('/[^0-9.,$-]/', '', $dato);
                    // Remover signos negativos
                    $importe = str_replace('-', '', $importe);
                    // Agregar el signo de peso al importe
                    $datosValidados['importe'] = '$' . $importe;
                    $importeEncontrado = true;
                } elseif ($dato === 'DEBITO AUTOMATICO') {
                    $datosValidados['referencia'] = str_pad('DEB AUTOM', 15);
                    $referenciaEncontrada = true;
                } elseif ($dato === 'DEBIN') {
                    $datosValidados['referencia'] = str_pad('DEBIN', 15);
                    $referenciaEncontrada = true;
                } elseif ($dato === 'TARJETA DE CREDITO') {
                    $datosValidados['referencia'] = str_pad('TARJ CREDITO', 15);
                    $referenciaEncontrada = true;
                }
            
                // Verifica si la referencia no se encontró y la establece en 15 espacios en blanco
                if (!$referenciaEncontrada) {
                    $datosValidados['referencia'] = str_repeat(' ', 15);
                }
                // Verifica si $identificadorUnico está presente
                if (is_null($identificadorUnico) && !empty($params) && isset($params['id'])) {
                    $datosValidados['identificador_alta_proveedores'] = $identificadorUnico;
                } elseif (is_null($identificadorUnico)) {
                    $datosValidados['identificador_alta_proveedores'] = null;
                }
            }
            
            // Agrega los datos preestablecidos a cada fila
            $datosValidados += $datosPreestablecidos;
        
            if (!empty($datosValidados)){
                $datosArchivoActual[] = $datosValidados;
            // Agrega los datos procesados solo si todos los campos requeridos están presentes
            if (!$cbuEncontrado) {
                $camposFaltantes[] = "CBU";
            }

            if (!$entidadEncontrada) {
                $camposFaltantes[] = "COD.ENTIDAD";
            }

            if (!$cuentaSucursalEncontrada) {
                $camposFaltantes[] = "COD.SUCURSAL";
            }

            if (!$cuitEncontrado) {
                $camposFaltantes[] = "CUIT";
            }

            if (!$importeEncontrado) {
                $camposFaltantes[] = "IMPORTE";
            }

            /* if (!$identificacionClienteEncontrada) {
                $camposFaltantes[] = "IDENTIFICACION CLIENTE";
            } */

            if(!empty($camposFaltantes)){
                $datosNoEncontrados[$contadorLinea] = $camposFaltantes;
            }
        }
        }

        $this->datosProcesadosTipo2 = array_merge($this->datosProcesadosTipo2, $datosArchivoActual);

        if(!empty($datosNoEncontrados)){
            $this->datosFaltantesTipo2 = $datosNoEncontrados;
            $this->noEncontradosTipo2($this->datosFaltantesTipo2);
        }

        $this->registrosArchivos[] = [
            'identificador_tipo2' => $identificadorTipo2,
            'nombre_archivo' => $this->archivo->getClientOriginalName(),
            'tipo_registro' => 'Registros tipo 2',
            'datos' => $datosArchivoActual,
        ];

        $this->cargando = false;
        sleep(1);

        $this->datosNoEncontrados = $datosNoEncontrados;

        $this->mostrarDatosTipo2 = true;

        $this->emit('datosTipo2Cargados', $this->totalImporteTipo2, $contadorRegistrosTipo2);
        
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

        public function mostrarDatosProcesados($datosProcesadosTipo2, $registrosArchivos, $cargando, $datosNoEncontrados)
        {
            $this->datosProcesadosTipo2 = $datosProcesadosTipo2;
            $this->registrosArchivos = $registrosArchivos;
            $this->cargando = $cargando;
            $this->datosNoEncontrados = $datosNoEncontrados;
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

    public function descargarDatosAltaProveedores()
{
    // Verifica que la sección actual sea "alta_proveedor" y que haya datos antes de generar el archivo
    if ($this->seccionSeleccionada === 'alta_proveedor' && count($this->datosAltaProveedor) > 0) {
        // Verifica si todos los campos necesarios están presentes en al menos una fila
        $camposNecesarios = ['cbu','id_tipo', 'tipo_cuenta', 'alias', 'cuit','titulares'];
        $datosFaltantes = [];

        foreach ($camposNecesarios as $campo) {
            $campoEncontrado = false;

            foreach ($this->datosAltaProveedor as $fila) {
                if (isset($fila[$campo]) && !empty($fila[$campo])) {
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
            $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo! Los siguientes campos son obligatorios: ' . implode(', ', $datosFaltantes);
            $this->mostrarMensajeError = true;
            $this->mostrarMensajeErrorAltaProveedores = true;
            $this->intentoDescarga = true;
            return;
        }

        // Genera el contenido del archivo TXT
        $contenido = '';
        foreach ($this->datosAltaProveedor as $fila) {
            if(isset($fila['cbu'])){
                $cbu = str_pad($fila['cbu'], 22, '0', STR_PAD_LEFT);
            }else{
                $this->datosNoEncontrados();
            }

            if(isset($fila['cuit'])){
                $cuit = str_pad($fila['cuit'], 11, '0', STR_PAD_LEFT);
            }else{
                $this->datosNoEncontrados();
            }
    
            if(isset($fila['alias'])){
                $alias = str_pad($fila['alias'], 22);
            }else{
                $this->datosNoEncontrados();
            }
           
            if(isset($fila['id_tipo'])){
                $idTipo = $fila['id_tipo'];
            }else{
                $this->datosNoEncontrados();
            }
    
            if(isset($fila['tipo_cuenta'])){
               $tipoCuenta = $fila['tipo_cuenta'];     
            }else{
                $this->datosNoEncontrados();
            }
    
            if(isset($fila['titulares'])){
                $titulares = $fila['titulares'];
            }else{
                $this->datosNoEncontrados();
            }
            // Formatea los campos según las longitudes
            $contenido .=
                $cbu .
                $cuit .
                $alias .
                $idTipo .
                $tipoCuenta .
                str_pad($fila['referencia'], 30) .
                str_pad($fila['email'], 50) .
                $titulares . "\n";
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
                'cbu',
                'entidad_acreditar',
                'cuit',
                'moneda',
                'fecha_pago',
                'info_criterio_empresa',
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
    
    public function descargarDatosRegistroTipo2()
    {
        // Restablece la variable $intentoDescarga
        $this->intentoDescarga = false;
    
        // Verifica que haya datos cargados en datosProcesadosTipo2
        if (count($this->datosProcesadosTipo2) > 0) {
            // Verifica que todos los campos necesarios estén presentes en al menos una fila
            $camposNecesarios = ['tipo_registro', 'entidad_acreditar', 'sucursal', 'cbu','cuit', 'importe','identificacion_cliente', 'nro_documento','sucursal_acreditar'];
    
            $datosFaltantes = [];
    
            foreach ($camposNecesarios as $campo) {
                $campoEncontrado = false;
    
                foreach ($this->datosProcesadosTipo2 as $fila) {
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
                $this->mostrarMensajeErrorTipo2 = true;
    
                // Establece el intento de descarga
                $this->intentoDescarga = true;
    
                // Retorna para no continuar con la descarga
                return;
            }
    
            // Genera el contenido del archivo TXT
            $contenido = '';
            $tipoRegistro = '2';
    
            foreach ($this->datosProcesadosTipo2 as $fila) {
                if(isset($fila['entidad_acreditar'])){
                    $entidadAcreditar = $fila['entidad_acreditar'];
                    $entidad = str_pad($entidadAcreditar, 4, '0', STR_PAD_LEFT);
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                $this->mostrarMensajeError = true;
                $this->mostrarMensajeErrorTipo2 = true;
    
                // Establece el intento de descarga
                $this->intentoDescarga = true;
    
                // Retorna para no continuar con la descarga
                return;
                }

                if(isset($fila['sucursal_acreditar'])){
                    $sucursalAcreditar = $fila['sucursal_acreditar'];
                    $sucursal = str_pad($sucursalAcreditar, 4, '0', STR_PAD_LEFT);
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                    $this->mostrarMensajeError = true;
                    $this->mostrarMensajeErrorTipo2 = true;
        
                    // Establece el intento de descarga
                    $this->intentoDescarga = true;
        
                    // Retorna para no continuar con la descarga
                    return;
                }

                if(isset($fila['cbu'])){
                    $cbu = $fila['cbu'];
                    // Obtener los primeros 8 dígitos
                    $primerBloque = substr($cbu, 0, 8);
    
                    // Obtener el último dígito del primer bloque
                    $ultimoDigito = substr($primerBloque, -1);
    
                    // Obtener los siguientes 14 dígitos
                    $segundoBloque = substr($cbu, 8, 14);
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                    $this->mostrarMensajeError = true;
                    $this->mostrarMensajeErrorTipo2 = true;
        
                    // Establece el intento de descarga
                    $this->intentoDescarga = true;
        
                    // Retorna para no continuar con la descarga
                    return;
                }

                if(isset($fila['importe'])){
                    $formatoDinero = $fila['importe'];
                    $formatoDinero = str_replace(['$', ','], '', $formatoDinero);
                    // Convierte la cadena a un número entero
                    $numeroEntero = intval($formatoDinero);
                    $numeroAjustado = str_pad((string)$numeroEntero, 10, '0', STR_PAD_LEFT);
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                    $this->mostrarMensajeError = true;
                    $this->mostrarMensajeErrorTipo2 = true;
        
                    // Establece el intento de descarga
                    $this->intentoDescarga = true;
        
                    // Retorna para no continuar con la descarga
                    return;
                }

                if(isset($fila['identificacion_cliente'])){
                    $identificacionCliente = str_pad($fila['identificacion_cliente'], 1, '0', STR_PAD_RIGHT); // Asegura una longitud de 1
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                    $this->mostrarMensajeError = true;
                    $this->mostrarMensajeErrorTipo2 = true;
        
                    // Establece el intento de descarga
                    $this->intentoDescarga = true;
        
                    // Retorna para no continuar con la descarga
                    return;
                }

                if(isset($fila['cuit'])){
                    $nroDocumento = str_pad($fila['cuit'], 11, '0', STR_PAD_RIGHT); // Asegura una longitud de 11
                }else{
                    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
                    $this->mostrarMensajeError = true;
                    $this->mostrarMensajeErrorTipo2 = true;
        
                    // Establece el intento de descarga
                    $this->intentoDescarga = true;
        
                    // Retorna para no continuar con la descarga
                    return;
                }

                if(isset($identificacionCliente)&& isset($nroDocumento)){
                    $identificacionNroDocumento = $identificacionCliente . $nroDocumento;
                    // Asegura que la longitud sea de 22 caracteres
                    $identificacionNroDocumento = str_pad($identificacionNroDocumento, 22, ' ', STR_PAD_RIGHT);
                }

                // Formatea los campos según las longitudes y concatena sin espacios
                $contenido .=
                    $fila['tipo_registro'] .
                    $entidad .
                    $sucursal .
                    $ultimoDigito .
                    $segundoBloque .
                    $numeroAjustado .
                    $fila['referencia'] .
                    $identificacionNroDocumento .
                    $fila['clase_documento'].
                    $fila['tipo_documento'].
                    $fila['nro_documento'] .
                    $fila['uso_BNA'] .
                    $fila['datos_de_la_empresa'] .
                    $fila['identificador_prestamo'] .
                    $fila['nro_operacion_link'] .
                    $fila['sucursal'] .
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

    public function eliminarUltimosDatos($identificadorUnico)
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

        $this->eliminarUltimoArchivoTipo1($identificadorUnico);
        $this->eliminarUltimosDatosTipo2($identificadorUnico);

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

        // Elimina los datos tipo 3 relacionados con el último archivo de "Registros Tipo 2"
        foreach ($this->datosProcesadosTipo3 as $index => $registroTipo3) {
            if ($registroTipo3['identificador_tipo2'] === $identificadorTipo2) {
                unset($this->datosProcesadosTipo3[$index]);
            }
        }

        // Recorre los datos de datosProcesadosTipo2 y elimina los que coincidan con el identificadorTipo2
        foreach ($this->datosProcesadosTipo2 as $index => $registro) {
            if ($registro['identificador_tipo2'] === $identificadorTipo2) {
                unset($this->datosProcesadosTipo2[$index]);
            }
        }

        // Reindexa los elementos después de eliminar
        $this->datosProcesadosTipo2 = array_values($this->datosProcesadosTipo2);

        // Elimina el último archivo de "Registros Tipo 2" de la lista de registrosArchivos
        unset($this->registrosArchivos[$ultimoIndice]);

        // Reindexa los elementos después de eliminar
        $this->registrosArchivos = array_values($this->registrosArchivos);

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