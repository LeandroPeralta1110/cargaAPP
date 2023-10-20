<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use App\Http\Livewire\CargaArchivo;
use App\Helpers\Expressions;
use App\Events\DatosProcesados;

class ProcesarArchivoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public $cargando = false;
    public $noEncontradosTipo2 = [];
    public $componenteLivewire = [];

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

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CargaArchivo $componenteLivewire)
{
    $this->componenteLivewire = $componenteLivewire;
}

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $validator = Validator::make(['archivo' => $this->archivo], [
            'archivo' => 'required|mimes:csv,txt,xlsx|max:2048',
        ]);
    
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
      
            $this->cargando = true;

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

        $this->componenteLivewire->datosTipo2Cargados($this->totalImporteTipo2, count($datosArchivoActual));
        event(new DatosProcesados($this->datosProcesadosTipo2, $this->registrosArchivos,$this->cargando,$this->datosNoEncontrados));

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
}
