<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DatosProcesados
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $datosArchivoActual;
    public $otrasVariables;
    public $registrosArchivos;
    public $cargando;
    public $datosNoEncontrados;
    public $datosProcesadosTipo2;

    /**
     * Create a new event instance.
     *
     * @return void
     */

    public function __construct($datosProcesadosTipo2, $registrosArchivos,$cargando,$datosNoEncontrados)
    {
        $this->datosProcesadosTipo2 = $datosProcesadosTipo2;
        $this->registrosArchivos = $registrosArchivos;
        $this->cargando = $cargando;
        $this->datosNoEncontrados = $datosNoEncontrados;
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
