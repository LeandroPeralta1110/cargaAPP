<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Helpers\Expressions;

class AltaProveedores extends Component
{
    use WithFileUploads;

    protected $listeners = ['datos-archivos-registrados' => 'agregarDatosArchivosRegistrados'];

    public $datosAltaProveedor = [];
    public $datosNoEncontradosAltaProveedor = [];
    public $registrosArchivos = [];
    public $mostrarDatosAltaProveedor = false;
    public $popupMessage ='';
    public $mensajeError = '';
    public $mostrarMensajeErrorAltaProveedores;
    public $mostrarMensajeError = false;
    public $intentoDescarga = false;

    public $datos = []; // Array para almacenar los datos procesados
    public $porPagina = 6; // Número de elementos por página
    public $pagina = 1;

    public $archivo;
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
    
        $this->emit('datos-archivos-registrados', [
            'nombre_archivo' => $this->archivo->getClientOriginalName(),
            'tipo_registro' => 'Alta Proveedores',
            'datos' => $datosArchivoActual,
        ]);
    
        $this->mostrarDatosAltaProveedor = true;
    
        $this->emit('datosAltaProveedorCargados', count($datosArchivoActual));
    
        if (!empty($datosNoEncontrados)) {
            $this->popupMessage = 'Datos no encontrados:<br>';
        
            foreach ($datosNoEncontrados as $linea => $camposFaltantes) {
                $this->popupMessage .= 'Línea ' . $linea . ': ' . implode(', ', $camposFaltantes) . '<br>';
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

    public function agregarDatosArchivosRegistrados($datos) {
        $this->registrosArchivos[] = $datos;
    }

    public function closePopup()
    {
        $this->popupMessage = '';
        $this->mensajeError = '';
        $this->mostrarMensajeErrorAltaProveedores = false;
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

        // Emite un evento para notificar a SeccionArchivosRegistrados
        $this->emit('eliminarUltimosDatos');

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

        if ($this->seccionSeleccionada === 'alta_proveedor') {
            $total = count($this->datosAltaProveedor);
            $datosPaginados = array_slice($this->datosAltaProveedor, $desde, $this->porPagina);
        } 

            return view('livewire.alta-proveedores', [
            'datos' => $datosPaginados,
            'total' => $total,
            'desde' => $desde,
            'hasta' => $hasta,
            'seccionActual' => $this->seccionSeleccionada,
        ]);
    }
    }
