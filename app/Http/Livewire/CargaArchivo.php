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
    public $datosFaltantesTipo2 = [];
    public $popupMessage;
    public $datosNoEncontrados = [];
    public $datosNoEncontradosAltaProveedor = [];
    public $popupMessageAltaProveedor;
    public $intentoDescarga;

    public $mensajeError = "";
    public $mostrarMensajeError = false;

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

    public function procesarArchivosAltaProveedores()
    {
        $this->validate([
            'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
        ]);
    
        $datosNoEncontrados = [];
        $datosArchivoActual = [];
    
        $contenido = file_get_contents($this->archivo->getRealPath());
        $lineas = explode("\n", $contenido);
    
        $contadorRegistrosAltaProveedor = 0;
        $contadorLinea = 0;
    
        foreach ($lineas as $linea) {
            // Incrementa el contador de línea
            $contadorLinea++;
    
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
    
        if (!empty($datosNoEncontrados)) {
            $this->popupMessage = 'Datos no encontrados:<br>';
        
            foreach ($datosNoEncontrados as $linea => $camposFaltantes) {
                $this->popupMessage .= 'Línea ' . $linea . ': ' . implode(', ', $camposFaltantes) . '<br>';
            }
        }
    
        return view('livewire.carga-archivo', [
            'datosNoEncontradosAltaProveedor' => $datosNoEncontrados,
            'datosAltaProveedor' => $datosArchivoActual,
        ]);
    }    

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
            'sistema_original' => str_pad('2', 2, ' ', STR_PAD_LEFT),
            'filler' => str_pad('15', 15, ' ', STR_PAD_LEFT),
            'casa_envio_rendicion' => str_pad('4',4,' ',STR_PAD_LEFT),
            'filler_100' => str_pad('100', 100, ' ', STR_PAD_LEFT),
        ];

        $datosValidados = [
            'tipo_registro' => '1',
        ];

        $cbuEncontrado = false; // Variable para verificar si se encontró CBU en esta línea
        $cuitEncontrado = false;
        $monedaEncontrada = false;
        $cuentaSucursalEncontrada = false;
        $fechaPagoEncontrada = false;
        $infoCriterioEmpresaEncontrada = false;
        $tipoPagoSueldosEncontrado = false;
        $codigoConvenioEncontrado = false;
        $numeroEnvioEncontrado = false;

        $camposFaltantes = []; // Reiniciar la variable en cada iteración

foreach ($datos as $key => $dato) {
    // Realiza la validación específica para cada tipo de dato
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
    } elseif (preg_match('/^[01]$/', $dato)) {
        $datosValidados['moneda'] = $dato;
        $monedaEncontrada = true;
    } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dato)) {
        $datosValidados['fecha_pago'] = $dato;
        $fechaPagoEncontrada = true;
    } elseif (preg_match('/^[\w\s]+$/', $dato)) {
        $datosValidados['info_criterio_empresa'] = $dato;
        $infoCriterioEmpresaEncontrada = true;
    } elseif (preg_match('/^\d{4}$|^\d{8}$/', $dato)) {
        $datosValidados['codigo_convenio'] = $dato;
        $codigoConvenioEncontrado = true;
    } elseif (preg_match('/^[12]$/', $dato)) {
        $datosValidados['numero_envio'] = $dato;
        $numeroEnvioEncontrado = true;
    }
}

// Agrega los datos preestablecidos a cada fila
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

    if (!$tipoPagoSueldosEncontrado) {
        $camposFaltantes[] = "Tipo de Pago de Sueldos";
    }

    if (!$codigoConvenioEncontrado) {
        $camposFaltantes[] = "Código de Convenio";
    }

    if (!$numeroEnvioEncontrado) {
        $camposFaltantes[] = "Número de Envío";
    }

    $datosNoEncontrados[$contadorLinea] = $camposFaltantes;
}
}

    $this->datosProcesadosTipo1 = array_merge($this->datosProcesadosTipo1, $datosArchivoActual);

    $this->registrosArchivos[] = [
        'nombre_archivo' => $this->archivo->getClientOriginalName(),
        'tipo_registro' => 'Registros tipo 1',
        'datos' => $datosArchivoActual,
    ];

    $this->mostrarDatosTipo1 = true;

    $this->emit('datosTipo1Cargados', count($datosArchivoActual));

    if (!empty($datosNoEncontrados)) {
        $this->popupMessage = 'Datos no encontrados:<br>';

        foreach ($datosNoEncontrados as $linea => $camposFaltantes) {
            $this->popupMessage .= 'Línea ' . $linea . ': ' . implode(', ', $camposFaltantes) . '<br>';
        }
    }

    $this->datosNoEncontrados = $datosNoEncontrados;

            return view('livewire.carga-archivo', [
                'datosNoEncontrados' => $datosNoEncontrados,
                'datosProcesadosTipo1' => $datosArchivoActual,
            ]);
        }

        public function datosNoEncontradosTipo1($datosfaltantes){
            if (!empty($datosfaltantes)) {
                $this->popupMessage = 'Datos no encontrados:<br>';
            
                foreach ($datosfaltantes as $linea => $camposFaltantes) {
                    $this->popupMessage .= 'Línea ' . $linea . ': ' . implode(', ', $camposFaltantes) . '<br>';
                }
            }
        }

public function cargaArchivoTipo2()
{
    $this->validate([
        'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
    ]);

    $datosNoEncontrados = [];
    $datosArchivoActual = [];

    $contenido = file_get_contents($this->archivo->getRealPath());
    $lineas = explode("\n", $contenido);

    $identificadorTipo2 = uniqid();
    $this->identificadorTipo2 = $identificadorTipo2;

    $contadorRegistrosTipo2 = 0;
    $contadorLinea = 0;

    foreach ($lineas as $linea) {
        // Incrementa el contador de línea
        $contadorLinea++;
    
        // Dividir la línea en elementos usando el punto y coma como separador
        $datos = str_getcsv($linea, ';');
    
        // Inicializa datos preestablecidos con ceros
        $datosPreestablecidos = [
            'clase_documento' => '00',
            'tipo_documento' => '00',
            'nro_documento' => str_pad('11', 11, '0', STR_PAD_LEFT),
            'estado' => '00',
            'datos_de_la_empresa' => str_pad('13', 13, ' ', STR_PAD_LEFT),
            'cuil_con_ceros'=> str_pad('11',11,'0'),
            'identificador_prestamo' => '0000',
            'nro_operacion_link' => str_pad('9', 9, ' ', STR_PAD_LEFT),
            'sucursal' => str_pad('4', 4, ' ', STR_PAD_LEFT),
            'numero_registro_link' => str_pad('6', 6, ' ', STR_PAD_LEFT),
            'observaciones' => str_pad('15', 15, '0', STR_PAD_LEFT),
            'filler' => str_pad('62', 62, ' ', STR_PAD_LEFT),
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
    
        foreach ($datos as $key => $dato) {
            // Realiza la validación específica para cada tipo de dato
            if($this->validarCBU($dato)) {
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
            } elseif ($this->validarImporte($dato)) {
                $importe = preg_replace('/[^0-9.,$-]/', '', $dato);
                // Remover signos negativos
                $importe = str_replace('-', '', $importe);
                // Agregar el signo de peso al importe
                $datosValidados['importe'] = '$' . $importe;
                $importeEncontrado = true;
            } elseif ($this->validarReferencia($dato)) {
                $datosValidados['referencia'] = $dato;
                $referenciaEncontrada = true;
            } elseif ($this->validarIdentificacionCliente($dato)) {
                $datosValidados['identificacion_cliente'] = $dato;
                $identificacionClienteEncontrada = true;
            }
        }
    
        // Agrega los datos preestablecidos a cada fila
        $datosValidados += $datosPreestablecidos;
    
        // Agrega los datos procesados solo si al menos uno de los campos requeridos está presente
        if (!empty($datosValidados)) {
            $datosArchivoActual[] = $datosValidados;
    
            // Verifica si se encontró CBU en esta línea y agrega el mensaje si no se encontró
            if (!$cbuEncontrado) {
                $datosNoEncontrados[$contadorLinea][] = "CBU";
            }
    
            // Verifica si se encontró Entidad en esta línea y agrega el mensaje si no se encontró
            if (!$entidadEncontrada) {
                $datosNoEncontrados[$contadorLinea][] = "COD.ENTIDAD";
            }
    
            if (!$cuentaSucursalEncontrada) {
                $datosNoEncontrados[$contadorLinea][] = "COD.SUCURSAL";
            }
    
            // Verifica si se encontró Cuenta o Sucursal en esta línea y agrega el mensaje si no se encontró
            if (!$cuitEncontrado) {
                $datosNoEncontrados[$contadorLinea][] = "CUIT";
            }
    
            if (!$importeEncontrado) {
                $datosNoEncontrados[$contadorLinea][] = "IMPORTE";
            }
    
            if (!$referenciaEncontrada) {
                $datosNoEncontrados[$contadorLinea][] = "REFERENCIA";
            }
    
            if (!$identificacionClienteEncontrada) {
                $datosNoEncontrados[$contadorLinea][] = "IDENTIFICACION CLIENTE";
            }
            $contadorRegistrosTipo2++;
        }
    }

    $this->datosProcesadosTipo2 = array_merge($this->datosProcesadosTipo2, $datosArchivoActual);

    $this->registrosArchivos[] = [
        'identificador_tipo2' => $identificadorTipo2,
        'nombre_archivo' => $this->archivo->getClientOriginalName(),
        'tipo_registro' => 'Registros tipo 2',
        'datos' => $datosArchivoActual,
    ];

    $this->totalImporteTipo2 = array_sum(array_column($datosArchivoActual, 'importe'));
    $this->mostrarDatosTipo2 = true;

    $this->emit('datosTipo2Cargados', $this->totalImporteTipo2, count($datosArchivoActual));

            $this->datosNoEncontrados = $datosNoEncontrados;

            return view('livewire.carga-archivo', [
                'datosNoEncontrados' => $datosNoEncontrados,
                'datosProcesadosTipo2' => $datosArchivoActual,
            ]);
        }

        public function noEncontradosTipo2($datosFaltantes){
            if (!empty($datosFaltantes)) {
                $this->popupMessage = 'Datos no encontrados:<br>';

        foreach ($datosNoEncontrados as $linea => $camposFaltantes) {
            $camposFaltantesUnicos = array_unique($camposFaltantes);
            $this->popupMessage .= 'Línea ' . $linea . ': ' . implode(', ', $camposFaltantesUnicos) . '<br>';
        }
    }

    $this->datosNoEncontrados = $datosNoEncontrados;

    return view('livewire.carga-archivo', [
        'datosNoEncontrados' => $datosNoEncontrados,
        'datosProcesadosTipo2' => $datosArchivoActual,
    ]);
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
    // Restablece la variable $intentoDescarga
    $this->intentoDescarga = false;

    // Verifica que haya datos cargados en datosProcesadosTipo2
    if (count($this->datosProcesadosTipo2) > 0) {
        // Verifica que todos los campos necesarios estén presentes en al menos una fila
        $camposNecesarios = ['tipo_registro', 'entidad_acreditar', 'sucursal', 'cbu', 'importe', 'referencia', 'identificacion_cliente', 'nro_documento', 'estado', 'datos_de_la_empresa', 'identificador_prestamo', 'nro_operacion_link', 'sucursal_acreditar', 'numero_registro_link', 'observaciones'];

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

            // Establece el intento de descarga
            $this->intentoDescarga = true;

            // Almacena los datos faltantes en la variable de sesión para mostrar en el popup
            session(['datosFaltantesTipo2' => $datosFaltantes]);

            // Retorna para no continuar con la descarga
            return;
        }

        // Genera el contenido del archivo TXT
        $contenido = '';
        $tipoRegistro = '2';

        foreach ($this->datosProcesadosTipo2 as $fila) {
            // Elimina los caracteres "$" y ","
            $formatoDinero = $fila['importe'];
            $formatoDinero = str_replace(['$', ','], '', $formatoDinero);

            // Convierte la cadena a un número entero
            $numeroEntero = intval($formatoDinero);

            // Formatea los campos según las longitudes y concatena sin espacios
            $contenido .=
                $fila['tipo_registro'] .
                $fila['entidad_acreditar'] .
                $fila['sucursal'] .
                $fila['cbu'] .
                $fila['importe'] .
                $fila['referencia'] .
                $fila['identificacion_cliente'] .
                $fila['nro_documento'] .
                $fila['estado'] .
                $fila['datos_de_la_empresa'] .
                $fila['identificador_prestamo'] .
                $fila['nro_operacion_link'] .
                $fila['sucursal_acreditar'] .
                $fila['numero_registro_link'] .
                $fila['observaciones'] . "\n";
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