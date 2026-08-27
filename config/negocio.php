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

    /*
    |--------------------------------------------------------------------------
    | Impuesto general a las ventas
    |--------------------------------------------------------------------------
    |
    | Los precios del catálogo ya incluyen el IGV, como es habitual en el
    | comercio minorista peruano. El comprobante lo desglosa hacia atrás:
    | la base imponible sale de dividir el total entre 1 + la tasa.
    |
    */

    'igv' => (float) env('NEGOCIO_IGV', 0.18),

];
