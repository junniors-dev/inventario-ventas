<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Datos del negocio
    |--------------------------------------------------------------------------
    |
    | Aparecen en la cabecera de los comprobantes en PDF. Se configuran por
    | entorno para que cada instalación use los suyos.
    |
    */

    'nombre' => env('NEGOCIO_NOMBRE', 'Bodega Central'),
    'ruc' => env('NEGOCIO_RUC'),
    'direccion' => env('NEGOCIO_DIRECCION'),
    'telefono' => env('NEGOCIO_TELEFONO'),

];
