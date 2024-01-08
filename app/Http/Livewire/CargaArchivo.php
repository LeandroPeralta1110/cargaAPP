<?php
//Componente donde se ecuentra toda la logica de el programa, tanto la carga, descarga y eliminacion de archivos.
//@autor= Leandro Peralta
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
    public $datosDuplicados= [];
    public $archivoAltaProveedores = [];
    public $contadorRegistrosAltaProveedor;
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
    public $archivoCargadoDesdeAltaProveedores = false;
    public $datos = []; 
    public $porPagina = 6; 
    public $pagina = 1;

    //secciones para el tipo de pago, predefinido alta proveedores
    public $seccionSeleccionada = "alta_proveedor";

    public function procesarArchivosAltaProveedores()
    {
        $this->validate([
            'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
        ]);
    
        $datosNoEncontrados = [];
        $datosArchivoActual = [];
        $filasProcesadas = [];
        $datosDuplicados = [];
    
        $contenido = $this->archivo->getClientOriginalExtension();
        $identificadorUnico = uniqid();
        
        if ($contenido == 'xlsx') {
            $spreadsheet = IOFactory::load($this->archivo->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $encabezado = [];
            $lineas = [];
            $primerFila = true; // Variable para rastrear la primera fila
            foreach ($worksheet->getRowIterator() as $row) {
                $cellIterator = $row->getCellIterator();
                $cellData = [];
                foreach ($cellIterator as $cell) {
                    $cellData[] = $cell->getValue();
                }
                if ($primerFila) {
                    $encabezado = $cellData; // Esto sigue siendo el encabezado en la fila 0
                    $primerFila = false;
                } else {
                    $lineas[] = $cellData; // Aquí empiezan los datos en la fila 1
                } 
            }
        } else {
            $contenido = file_get_contents($this->archivo->getRealPath());
            $lineas = explode("\n", $contenido);
            $encabezado = [];
        
            // Verifica si hay un encabezado antes de intentar eliminarlo
            if (!empty($lineas[0])) {
                $encabezado = str_getcsv($lineas[0], ';');
                unset($lineas[0]);
            }
        }
        
        $contadorRegistrosAltaProveedor = 1;
        $datosArchivoOriginal = [];
        
        // Ajusta el índice inicial dependiendo del tipo de contenido
        $indiceInicial = ($contenido == 'xlsx') ? 0 : 1;
       
        for ($contadorLinea = $indiceInicial; $contadorLinea < count($lineas); $contadorLinea++) {
            $linea = $lineas[$contadorLinea];
        
            // Verifica si $linea es un array y si es así, simplemente úsalo como está, de lo contrario, divídelo
            $datos = is_array($linea) ? $linea : str_getcsv($linea, ';');
            // Inicializa variables para verificar si se han encontrado los campos requeridos
            $cbuEncontrado = false;
            $aliasEncontrado = false;
            $idTipoEncontrado = false;
            $cuitEncontrado = false;
            $tipoCuentaEncontrado = false;
            $referenciaEncontrada = false;
            $emailEncontrado = false;
            $numeroComprobante = null;
            $num_fac = null;
    
            // Arreglo para los datos validados
            $camposFaltantes = []; 

            $datosValidados = [
                'titulares' => '1',
                'id_tipo' => '1',
                'tipo_cuenta' => '01',
            ];

            foreach ($datos as $key => $dato) {
                // Realiza la validación específica para cada tipo de dato
                // CBU
                if ($this->validarCBU($dato)) {
                    $datosValidados['cbu'] = $dato;
                    $cbuEncontrado = true;
                } /* elseif ($dato === '1' || $dato === '2'|| $dato === '3') { // Id Tipo Clave
                    $datosValidados['id_tipo'] = $dato;
                    $idTipoEncontrado = true;
                } */ elseif (in_array($dato, ['01', '02', '03', '04', '05', '06', '07'])) { // Tipo de Cuenta
                    $datosValidados['tipo_cuenta'] = $dato;
                    $tipoCuentaEncontrado = true;
                } elseif ($this->validarCUIT($dato)) { // CUIT
                    $datosValidados['cuit'] = $dato;
                    $cuitEncontrado = true;
                }elseif (preg_match('/^[\p{L}\s,.-]{8,50}$/', $dato)) {
                    if (!$referenciaEncontrada) {
                        $referencia = substr($dato, 0, 30);
                        $referencia = str_pad($referencia, 30, ' ', STR_PAD_RIGHT);
                        $datosValidados['referencia'] = $referencia;
                        $referenciaEncontrada = true;
                    } elseif (!$aliasEncontrado && preg_match('/[A-Za-z0-9.-]+/', $dato)) {
                        $alias = substr($dato, 0, 22);
                        $alias = str_pad($alias, 22, ' ', STR_PAD_RIGHT);
                        $datosValidados['alias'] = $alias;
                        $aliasEncontrado = true;
                    }
                } elseif (!$emailEncontrado && preg_match('/^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $dato)) {
                    $email = substr($dato, 0, 50);
                    $email = str_pad($email, 50, ' ', STR_PAD_RIGHT);
                    $datosValidados['email'] = $email;
                    $emailEncontrado = true;
                }elseif(preg_match('/^\d{8}$/', $dato)) {
                    // Este dato parece ser el número de comprobante (8 dígitos)
                    if ($numeroComprobante === null) {
                        $numeroComprobante = $dato;
                    }
                } elseif (preg_match('/^FC [A-Z] [A-Z0-9]+\-[A-Z0-9]+$/', $dato)) {
                    // Esto parece ser la referencia (ejemplo: 'FC C 00003-00000374')
                    if ($num_fac === null) {
                        $num_fac = $dato;
                    }
                }
            }

                if (!$emailEncontrado) {
                    $datosValidados['email'] = str_repeat(' ', 49);
                }

                if (!$referenciaEncontrada) {
                    $datosValidados['referencia'] = str_repeat(' ', 30);
                }

                if (!$aliasEncontrado) {
                    $datosValidados['alias'] = str_pad('', 22, ' ', STR_PAD_RIGHT);
                } else {
                    $datosValidados['alias'] = str_pad('', 22, '0', STR_PAD_LEFT);
                }

        $existeDuplicado = $this->existeDuplicado($numeroComprobante, $num_fac, $filasProcesadas);

        if ($existeDuplicado) {
            // Agrega información adicional al registro de duplicados
            $this->notificarDuplicado($contadorLinea +1, $numeroComprobante, $num_fac, $datosDuplicados, $identificadorUnico);
            // No es un duplicado, agrega esta fila a las filas procesadas
        } else {
            $filasProcesadas[] = [
                'contadorLinea' => $contadorLinea,
                'numeroComprobante' => $numeroComprobante,
                'num_fac' => $num_fac,
                'identificador_duplicados' => $identificadorUnico, // Vincula el identificador único del archivo
            ];
            $datosValidados['num_fac'] = $num_fac;
            
            $datosArchivoOriginal[] = $datos;

            // Agrega los datos preestablecidos a cada fila
            // Verifica si todos los campos requeridos se encontraron
            if (!empty($datosValidados)){
                $datosArchivoActual[] = $datosValidados;
                if (!$cbuEncontrado) {
                    $camposFaltantes[] = "CBU";
                }
                /* if (!$aliasEncontrado) {
                    $camposFaltantes[] = "Alias";
                } */
                /* if (!$idTipoEncontrado) {
                    $camposFaltantes[] = "Id Tipo Clave";
                } */
                if (!$cuitEncontrado) {
                    $camposFaltantes[] = "CUIT";
                }
                /* if (!$tipoCuentaEncontrado) {
                    $camposFaltantes[] = "Tipo de Cuenta";
                } */
                /* if (!$email) {
                    $camposFaltantes[] = "email";
                } */
                if(!empty($camposFaltantes)){
                    $datosNoEncontrados[$contadorLinea] = $camposFaltantes;
                }
            }
            $this->contadorRegistrosAltaProveedor++;
        }
        }

        $this->datosDuplicados = array_merge($this->datosDuplicados, $datosDuplicados);
       
        $archivoOriginalSinDuplicados = tempnam(sys_get_temp_dir(), 'original_');
        
        if (!empty($datosArchivoOriginal)) {
            $datosArchivoTexto = [];
            foreach ($datosArchivoOriginal as $datosFila) {
                $datosArchivoTexto[] = implode(';', $datosFila);
            }

            $encabezadoTexto = implode(';', $encabezado);
            $contenidoArchivoTexto = $encabezadoTexto . "\n" . implode("\n", $datosArchivoTexto);
            $contenidoArchivoTexto = rtrim($contenidoArchivoTexto) . "\n";
        } else {
            // Si no hay datos, simplemente guarda el encabezado
            file_put_contents($archivoOriginalSinDuplicados, $encabezado);
        }

        $this->archivoAltaProveedores = $archivoOriginalSinDuplicados;
        
        $this->datosAltaProveedor = array_merge($this->datosAltaProveedor, $datosArchivoActual);

        $this->datosNoEncontradosAltaProveedor = $datosNoEncontrados;
        
        $this->registrosArchivos[] = [
            'nombre_archivo' => $this->archivo->getClientOriginalName(),
            'tipo_registro' => 'Alta Proveedores',
            'identificadorUnico' => $identificadorUnico, // Vincula el identificador único del archivo
            'datos' => $datosArchivoActual,
        ];
    
        $this->mostrarDatosAltaProveedor = true;
    
        $this->emit('datosAltaProveedorCargados', count($datosArchivoActual));

        $this->archivoCargado = $this->archivo;

        $this->identificadorUnico = $identificadorUnico;

        $this->archivoCargadoDesdeAltaProveedores = true;
        // Llama a cargaArchivoTipo1 pasando el archivo original sin duplicados
        $this->cargaArchivoTipo1($identificadorUnico, $contenidoArchivoTexto);
        $this->cargaArchivoTipo2($identificadorUnico, $contenidoArchivoTexto);

    
        if (!empty($datosNoEncontrados)) {
            $this->popupMessage = 'Datos no encontrados:<br>';
        
            foreach ($datosNoEncontrados as $linea => $camposFaltantes) {
                $this->popupMessage .= 'Línea ' . $linea . ': ' . implode(', ', $camposFaltantes) . '<br>';
            }
        }
    }    

public function cargaArchivoTipo1($params = null,$archivoOriginalSinDuplicados = null)
{
    $this->validate([
        'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
    ]);

    $datosNoEncontrados = [];
    $datosArchivoActual = [];

    $contenido = $this->archivoCargadoDesdeAltaProveedores ? $archivoOriginalSinDuplicados : file_get_contents($this->archivo->getRealPath());

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

        $infoCriterioEmpresa = str_pad($infoCriterioEmpresa,20,' ', STR_PAD_RIGHT);

        $fechaActual = Carbon::now();

        // Retroceder al día hábil anterior (lunes a viernes)
        $diaHabilAnterior = $fechaActual->subWeekday();

        // Formatear la fecha según tus necesidades
        $fechaFormateada = $diaHabilAnterior->format("d/m/Y");

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
            'cuit' => '30537882871',
            'entidad_acreditar'=> '0047',
            'cbu' => '20004700327515',
            'moneda'=> '0',
            'fecha_pago' => $fechaFormateada,
            'info_criterio_empresa' => $infoCriterioEmpresa,
            'numero_envio' => '1',
            'codigo_convenio' => '0470032751'
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
            /* if ($this->validarCBU($dato)) {
                $datosValidados['cbu'] = $dato;
                $cbuEncontrado = true; */
                // Divide el CBU en entidad y sucursal
                /* $entidad = substr($dato, 4, 3);
                $datosValidados['entidad_acreditar'] = $entidad; */
             /* elseif ($this->validarCUIT($dato)) {
                $datosValidados['cuit'] = $dato;
                $cuitEncontrado = true; */
            /* } elseif ($numeroEnvioEncontrado === false && ($dato === '1' || $dato === '2')) {
                $datosValidados['numero_envio'] = $dato;
                $numeroEnvioEncontrado = true; */
            /* } elseif (preg_match('/^\d{4}$|^\d{8}$/', $dato)) {
                // Si $dato contiene solo números, asignarlo a $codConvenio
                $datosValidados['codigo_convenio'] = $dato;
                $codigoConvenioEncontrado = true; */
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

            /* if (!$cbuEncontrado) {
                $camposFaltantes[] = "CBU";
            } */

            /* if (!$cuitEncontrado) {
                $camposFaltantes[] = "CUIT";
            } */

            /* if (!$monedaEncontrada) {
                $camposFaltantes[] = "Moneda";
            } */

            /* if (!$fechaPagoEncontrada) {
                $camposFaltantes[] = "Fecha de Pago";
            } */

           /*  if (!$infoCriterioEmpresaEncontrada) {
                $camposFaltantes[] = "Información de Criterio de Empresa";
            } */

           /*  if (!$codigoConvenioEncontrado) {
                $camposFaltantes[] = "Código de Convenio";
            } */

            /* if (!$numeroEnvioEncontrado) {
                $camposFaltantes[] = "Número de Envío";
            } */
            
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

        public function cargaArchivoTipo2($params = null,$archivoOriginalSinDuplicados = null)
        {
            $this->validate([
                'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
            ]);
            $this->cargando = true;

            $contenido = $this->archivoCargadoDesdeAltaProveedores ? $archivoOriginalSinDuplicados : file_get_contents($this->archivo->getRealPath());

            if (!empty($params) && isset($params['id'])) {
                $identificadorUnico = $params['id'];
            } else {
                $identificadorUnico = null; // Otra acción si no se proporciona $params
            }
        
            $datosNoEncontrados = [];
            $datosArchivoActual = [];
            $filasProcesadas = [];

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
            $numeroComprobante = null;
            $referencia = false;
        
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
                    
                    // Agregar ".00" si no hay punto ni coma en el importe
                    if (!strpos($importe, '.') && !strpos($importe, ',')) {
                        $importe .= '.00';
                    }
                
                    // Agregar el signo de peso al importe
                    $datosValidados['importe'] = '$' . $importe;
                    $importeEncontrado = true;
                }elseif (preg_match('/^FC [A-Z] [A-Z0-9]+\-[A-Z0-9]+$/', $dato)) {
                    $referencia = substr($dato, 0, 15);
                    $referencia = str_pad($referencia, 15, ' ', STR_PAD_RIGHT);
                
                    // Eliminar el guion (-) de la cadena referencia
                    $referencia = str_replace('-', ' ', $referencia);
                
                    $datosValidados['referencia'] = $referencia;
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

            /* $existeDuplicado = $this->existeDuplicado($numeroComprobante, $referencia, $filasProcesadas); */

        /* if ($existeDuplicado) {
            // Llamar a la función notificarDuplicado si es un duplicado
            $this->notificarDuplicado($contadorLinea, $numeroComprobante, $referencia, $datosDuplicados,$identificadorUnico);
        } else { */
            // No es un duplicado, agrega esta fila a las filas procesadas
            $filasProcesadas[] = [
                'numeroComprobante' => $numeroComprobante,
                'num_fac' => '',
            ];

            $datosValidados['referencia'] = $referencia;

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
       /*  } */
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

        private function existeDuplicado($numeroComprobante, $num_fac, $filasProcesadas)
        {
            foreach ($filasProcesadas as $fila) {
                if ($fila['numeroComprobante'] === $numeroComprobante) {
                    // Si encuentras un número de comprobante igual, descarta la fila
                    return true;
                }
            }
            return false;
        }


        private function notificarDuplicado($contadorLinea, $numeroComprobante, $num_fac, &$datosDuplicados,$identificadorUnico)
        {
            // Almacena información sobre las filas duplicadas
            $datosDuplicados[] = [
                'contadorLinea' => $contadorLinea,
                'numeroComprobante' => $numeroComprobante,
                'num_fac' => $num_fac,
                'identificador_duplicados' => $identificadorUnico, 
            ];
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
    // Realiza la validación para Importe aquí, devuelve el importe si es válido, false en caso contrario
    if (preg_match('/^\$?([1-9]\d*(?:[.,]\d{2})?|[1-9]\d*)$/', $dato, $matches)) {
        $importe = floatval(str_replace(',', '.', $matches[1]));

        // Asegúrate de que el importe sea mayor a 2
        if ($importe > 2) {
            // Devuelve el importe con dos decimales
            return number_format($importe, 2, '.', '');
        }
    }
    return false;
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
                        $totalImporteTipo2Formateado = str_replace('.', '', $totalImporteTipo2); // Eliminar el punto
                        $totalImporteTipo2Formateado = str_pad($totalImporteTipo2Formateado, 15, '0', STR_PAD_LEFT); // Rellenar con ceros
                        $totalRegistrosTipo2++;
                    }
                }

                $totalRegistrosTipo2 = str_pad($totalRegistrosTipo2, 7, '0', STR_PAD_LEFT);

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

                $filler = str_repeat(' ', 83);

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
        // Verifica que la sección actual sea "alta_proveedor" y que haya datos antes de generar los archivos
        if ($this->seccionSeleccionada === 'alta_proveedor' && count($this->datosAltaProveedor) > 0) {
            // Genera el contenido del archivo TXT
            $contenido = '';
            $contenido2 = '';
            
            // Obtén la cantidad máxima de registros por tipo
            $maxRegistrosPorTipo = max(
               /*  count($this->datosProcesadosTipo1), */
                count($this->datosProcesadosTipo2),
                count($this->datosAltaProveedor)
            );
            // Itera sobre los índices
            // Agrega los datos de cada tipo, si existen
           
            $contenido .= $this->descargarDatosRegistroTipo1();
            
            for ($indice = 0; $indice < $maxRegistrosPorTipo; $indice++) {
            
                if ($indice < count($this->datosProcesadosTipo2)) {
                    $contenido .= $this->descargarDatosRegistroTipo2($indice);
                }
    
                if ($indice < count($this->datosAltaProveedor)) {
                    $contenido2 .= $this->concatenarDatosAltaProveedores($indice);
                }
            }
        
            $contenido .= $this->descargarDatosRegistroTipo3();
        
            // Agrega la información específica a $contenido2
            $contenido2 .= $this->generarInformacionEspecial();
            
            // Define los nombres de los archivos
            $nombreArchivo = 'datos_pago_proveedores.txt';
            $nombreArchivo2 = 'datos_alta_proveedores.txt';
        
            // Convertir a codificación de caracteres ANSI
            $contenido = mb_convert_encoding($contenido, 'Windows-1252', 'UTF-8');
            $contenido2 = mb_convert_encoding($contenido2, 'Windows-1252', 'UTF-8');
    
            // Crea los archivos en el almacenamiento temporal
            file_put_contents($nombreArchivo, $contenido);
            file_put_contents($nombreArchivo2, $contenido2);
    
            // Descarga los dos archivos en un archivo ZIP
            $zipFile = 'archivos_descargados.zip';
            $zip = new \ZipArchive();
            $zip->open($zipFile, \ZipArchive::CREATE);
    
            $zip->addFile($nombreArchivo, basename($nombreArchivo));
            $zip->addFile($nombreArchivo2, basename($nombreArchivo2));
    
            $zip->close();
    
            // Proporciona una respuesta para descargar el archivo ZIP
            return response()->download($zipFile)->deleteFileAfterSend(true);
        }
    }    
    
    private function generarInformacionEspecial() {
        // Aquí defines la lógica para generar la información especial
        // según el formato especificado
        return str_pad(count($this->datosAltaProveedor), 5, '0', STR_PAD_LEFT) . str_repeat(' ', 134) . "\n";
    }
    
  public function concatenarDatosAltaProveedores($indice){
    $contenido = '';
    if (count($this->datosProcesadosTipo1) > $indice) {
        // Verifica que todos los campos necesarios estén presentes en al menos una fila
        $camposNecesarios = [
            'referencia',
            'cuit',
            'tipo_cuenta',
            'cbu',
            'email',
        ];

        $datosFaltantes = [];

        $fila = $this->datosAltaProveedor[$indice];

        foreach ($camposNecesarios as $campo) {
            if (!isset($fila[$campo])) {
                $datosFaltantes[] = $campo;
            }
        }

        if (!empty($datosFaltantes)) {
            // Al menos un campo necesario está faltante
            $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
            $this->mostrarMensajeError = true;
            $this->mostrarMensajeErrorTipo1 = true;
            $this->mostrarDatosFaltantesTipo1 = $datosFaltantes;

            // Establece el intento de descarga
            $this->intentoDescarga = true;

            // Retorna para no continuar con la descarga
            return $contenido;
        }

        if (isset($fila['referencia'])) {
            $referencia = $fila['referencia'];
        } else {
            $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
            $this->mostrarMensajeError = true;
            $this->mostrarMensajeErrorTipo1 = true;
            $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;

            // Establece el intento de descarga
            $this->intentoDescarga = true;

            // Retorna para no continuar con la descarga
            return;
        }

        if (isset($fila['cuit'])) {
            $cuit = $fila['cuit'];
        } else {
            $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
            $this->mostrarMensajeError = true;
            $this->mostrarMensajeErrorTipo1 = true;
            $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;

            // Establece el intento de descarga
            $this->intentoDescarga = true;

            // Retorna para no continuar con la descarga
            return;
        }

        if (isset($fila['tipo_cuenta'])) {
            $tipo_cuenta = $fila['tipo_cuenta'];
        } else {
            $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
            $this->mostrarMensajeError = true;
            $this->mostrarMensajeErrorTipo1 = true;
            $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;

            // Establece el intento de descarga
            $this->intentoDescarga = true;

            // Retorna para no continuar con la descarga
            return;
        }

        if (isset($fila['cbu'])) {
            $cbu = $fila['cbu'];
        } else {
            $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
            $this->mostrarMensajeError = true;
            $this->mostrarMensajeErrorTipo1 = true;
            $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;

            // Establece el intento de descarga
            $this->intentoDescarga = true;

            // Retorna para no continuar con la descarga
            return;
        }
        if (isset($fila['email'])) {
            $email = $fila['email'];
        } else {
            $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
            $this->mostrarMensajeError = true;
            $this->mostrarMensajeErrorTipo1 = true;
            $this->mostrarDatosFaltantesTipo1 = $this->datosFaltantesTipo1;

            // Establece el intento de descarga
            $this->intentoDescarga = true;

            // Retorna para no continuar con la descarga
            return;
        }
        $contenido .=
                $cbu .
                $fila['alias'].
                $fila['id_tipo'].
                $cuit . 
                $fila['tipo_cuenta'] .
                $fila['referencia'] .
                $email . 
                $fila['titulares'] ."\n" 
                ;
            }
            return $contenido;
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
    $contenido = '';

    if (count($this->datosProcesadosTipo1) > 0) {
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

        $fila = $this->datosProcesadosTipo1[0]; // Accede al primer elemento del array

        foreach ($camposNecesarios as $campo) {
            if (!isset($fila[$campo]) || empty($fila[$campo])) {
                $datosFaltantes[] = $campo;
            }
        }

        // Procesa los datos y genera el contenido del archivo
        $cuentaSuc = str_pad($fila['entidad_acreditar'], 4, '0', STR_PAD_LEFT);

        $primerBloque = substr($fila['cbu'], 0, 8);
        $segundoBloque = '2' . substr($fila['cbu'], 9, 13);
        $fecha = DateTime::createFromFormat('d/m/Y', $fila['fecha_pago']);
        $fechaFormateada = $fecha->format('Ymd');
        $numeroEnvioFormateado = str_pad($fila['numero_envio'], 6, '0', STR_PAD_LEFT);

        $contenido .=
            $fila['tipo_registro'] .
            $fila['cuit'] .
            $cuentaSuc .
            $fila['cbu'] .
            $fila['moneda'] .
            $fechaFormateada .
            substr($fila['info_criterio_empresa'], 0, 40) .
            $fila['tipo_pagos'] .
            $fila['clase_pagos'] .
            $fila['codigo_convenio'] .
            $numeroEnvioFormateado .
            $fila['sistema_original'] .
            $fila['filler'] .
            $fila['casa_envio_rendicion'] .
            $fila['filler_100'] . "\n";

        return $contenido;
    }
}
    
public function descargarDatosRegistroTipo2($indice)
{
    // Restablece la variable $intentoDescarga
    $this->intentoDescarga = false;

    // Verifica que haya datos cargados en datosProcesadosTipo2 y que el índice sea válido
    if (count($this->datosProcesadosTipo2) > $indice) {
        // Verifica que todos los campos necesarios estén presentes en al menos una fila
        $camposNecesarios = ['tipo_registro', 'entidad_acreditar', 'cbu', 'cuit', 'importe', 'identificacion_cliente', 'nro_documento', 'sucursal_acreditar'];

        $datosFaltantes = [];

        // Utiliza el índice $indice para acceder a los datos del cliente específico
        $fila = $this->datosProcesadosTipo2[$indice];

        foreach ($camposNecesarios as $campo) {
            if (!isset($fila[$campo])) {
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
            return '';
        }

        // Genera el contenido del archivo TXT
        $contenido = '';
        $tipoRegistro = '2';

        if (isset($fila['entidad_acreditar'])) {
            $entidadAcreditar = $fila['entidad_acreditar'];
            $entidad = str_pad($entidadAcreditar, 4, '0', STR_PAD_LEFT);
        } else {
            $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo!';
            $this->mostrarMensajeError = true;
            $this->mostrarMensajeErrorTipo2 = true;

            // Establece el intento de descarga
            $this->intentoDescarga = true;

            // Retorna para no continuar con la descarga
            return $contenido;
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
                    $formatoDinero = str_replace(['$', ',','.'], '', $formatoDinero);
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
                    $identificacionNroDocumento = $nroDocumento;
                    // Asegura que la longitud sea de 22 caracteres
                    $identificacionNroDocumento = str_pad($identificacionNroDocumento, 21, ' ', STR_PAD_RIGHT);
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
                    '1'.
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
    
           /*  // Define el nombre del archivo
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
            ); */
            return $contenido;
        }

public function descargarDatosRegistroTipo3()
{
   // Inicializa la variable de contenido
   $ultimaFilaFormateada = '';

   // Verifica que la sección actual sea "registro_tipo_3" y que haya datos antes de generar el archivo
   if (!empty($this->ultimaFilaTipo3)) {
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
            $ultimaFila['filler']."\n"
        );

        /* // Define el nombre del archivo
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
        ); */
        return $ultimaFilaFormateada;
    }
    return $ultimaFilaFormateada;
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

            // Obtén el identificador del archivo que se eliminará
            $identificadorArchivoAEliminar = $this->registrosArchivos[$ultimoIndice]['identificadorUnico'];
    
             // Elimina los registros duplicados vinculados al identificador de archivo que se eliminará
            if (!empty($this->datosDuplicados)) {
                $this->datosDuplicados = array_filter($this->datosDuplicados, function ($duplicado) use ($identificadorArchivoAEliminar) {
                    return $duplicado['identificador_duplicados'] !== $identificadorArchivoAEliminar;
                });
            }

            // Elimina el último archivo de "Alta Proveedores" de la lista de registrosArchivos
            unset($this->registrosArchivos[$ultimoIndice]);
            $this->registrosArchivos = array_values($this->registrosArchivos);

            $this->datosDuplicados = array_values($this->datosDuplicados);
    
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