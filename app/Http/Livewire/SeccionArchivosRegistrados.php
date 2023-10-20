<?php

namespace App\Http\Livewire;

use Livewire\Component;

class SeccionArchivosRegistrados extends Component
{
    // En el componente SeccionArchivosRegistrados
    protected $listeners = ['datos-archivos-registrados' => 'procesarArchivos'];
    public $registrosArchivos = [];

    public function procesarArchivos($data)
    {
        // Maneja los datos recibidos
        $this->registrosArchivos[] = $data;
    }
    public function render()
    {
        return view('livewire.seccion-archivos-registrados');
    }
}
