<?php

namespace App\Http\Livewire;

use Livewire\Component;
use App\Helpers\Expressions;
use Livewire\WithFileUploads;

class RegistrosTipo2 extends Component
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
                    } elseif ($dato === 'DEBITO AUTOMATICO' || $dato === 'DEBIN' || $dato === 'TARJETA DE CREDITO') {
                        $datosValidados['referencia'] = $dato;
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

            $this->mostrarDatosTipo2 = true;

            $this->emit('datosTipo2Cargados', $this->totalImporteTipo2, count($datosArchivoActual));

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

public function datosNoEncontrados(){
    $this->mensajeError = '¡Faltan datos necesarios para descargar el archivo! Los siguientes campos son obligatorios: ';
    $this->mostrarMensajeError = true;
    $this->mostrarMensajeErrorAltaProveedores = true;
    $this->intentoDescarga = true;
    return;
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

        if ($this->seccionSeleccionada === 'registro_tipo_2') {
            $total = count($this->datosProcesadosTipo2);
            $datosPaginados = array_slice($this->datosProcesadosTipo2, $desde, $this->porPagina);
        }
        
        return view('livewire.registros-tipo2');
    }
}
