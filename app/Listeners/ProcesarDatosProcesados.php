<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\DatosProcesados;
use App\Http\Livewire\CargaArchivo;
use Livewire\Livewire;
use Mockery;

class ProcesarDatosProcesados
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(DatosProcesados $event)
{
    $componenteCargaArchivo = Livewire::test(CargaArchivo::class);
    // Obtén los datos del evento
    $datosProcesadosTipo2 = $event->datosProcesadosTipo2;
    $registrosArchivos = $event->registrosArchivos;
    $cargando = $event->cargando;
    $datosNoEncontrados = $event->datosNoEncontrados;
    $mostrarDatosTipo2 = $event->mostrarDatosTipo2;

    // Actualiza las propiedades del componente Livewire
    $componenteCargaArchivo->set('datosProcesadosTipo2', $datosProcesadosTipo2);
    $componenteCargaArchivo->set('registrosArchivos', $registrosArchivos);
    $componenteCargaArchivo->set('cargando', $cargando);
    $componenteCargaArchivo->set('datosNoEncontrados', $datosNoEncontrados);
    $componenteCargaArchivo->set('mostrarDatosTipo2', $mostrarDatosTipo2);
}
}
