<?php

use Illuminate\Support\Facades\Route;

use App\Http\Livewire\CargaArchivo;
use App\Http\Livewire\Cobranzas;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', CargaArchivo::class)->name('cargar-archivo');

route::get('/cobranzas', Cobranzas::class)->name('cobranzas');
