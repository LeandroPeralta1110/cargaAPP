<?php

namespace App\Helpers;

class Expressions {
    public static $expresionEntidad = '/^(\d{4}|\d{3})$/';
    public static $expresionCuentaSucursal = '/^(\d{4}|\d{3})$/';
    public static $expresionCBU = '/^\d{8}-\d{17}$/';
    public static $expresionCUIT = '/^\d{11}$/';
    public static $expresionImporte = '/^\$\d{1,3}(,\d{3})*(\.\d{2})?$/';
    public static $expresionReferencia = '/^[A-Za-z\s]+$/';
    public static $expresionIdentificacionCliente = '/^[1-3]\d{10}$/';
}