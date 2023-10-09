<?php

namespace App\Helpers;

class Expressions {
    public static $expresionEntidad = '/^\d{4}$/';
    public static $expresionCuentaSucursal = '/^\d{4}$/';
    public static $expresionCBU = '/^\d{22}$/';
    public static $expresionCUIT = '/^\d{11}$/';
    public static $expresionImporte = '/^-?[\d.,]+$/';
    public static $expresionReferencia = '/^[A-Za-z\s]+$/';
    public static $expresionIdentificacionCliente = '/^\d{11}$/';
}