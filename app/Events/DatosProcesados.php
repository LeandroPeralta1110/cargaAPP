<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Http\Livewire\CargaArchivo;
use Mockery;

class DatosProcesados
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $datosArchivoActual;
    public $otrasVariables;
    public $registrosArchivos;
    public $cargando;
    public $datosNoEncontrados;
    public $datosProcesadosTipo2;
    public $mostrarDatosTipo2;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public function __construct($datosProcesadosTipo2, $registrosArchivos,$cargando,$datosNoEncontrados,$mostrarDatosTipo2)
    {
        $this->datosProcesadosTipo2 = $datosProcesadosTipo2;
        dd($this->datosProcesadosTipo2);
        $this->registrosArchivos = $registrosArchivos;
        $this->cargando = $cargando;
        $this->datosNoEncontrados = $datosNoEncontrados;
        $this->mostrarDatosTipo2 = $mostrarDatosTipo2;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
