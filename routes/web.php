<?php

use App\Http\Controllers\clientController;
use App\Http\Controllers\CrudClient as ControllersCrudClient;
use App\Http\Controllers\pruebaController;
use Illuminate\Support\Facades\Route;
use App\Http\Livewire\CargaArchivo;
use App\Http\Livewire\ArchivosFrances;
use App\Http\Livewire\Cobranzas;
use Illuminate\Support\Facades\Auth;

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
Route::get('/alta-proveedores/banco-frances', ArchivosFrances::class)->name('archivo-frances');
Route::get('/cobranzas', Cobranzas::class)->name('cobranzas');
Route::resource('clients', clientController::class);
Auth::routes();
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/base', [Cobranzas::class, 'index']);


/* Route::get('/test-db-connection', function () {
    try {
        DB::connection()->getPdo();
        return "Conexión exitosa a la base de datos SQL Server.";
    } catch (\Exception $e) {
        return "Error de conexión: " . $e->getMessage();
    }
});  */