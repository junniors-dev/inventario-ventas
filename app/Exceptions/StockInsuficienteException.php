<?php

namespace App\Exceptions;

use App\Models\Producto;
use RuntimeException;

class StockInsuficienteException extends RuntimeException
{
    public static function para(Producto $producto, int $solicitado): self
    {
        return new self(sprintf(
            'Stock insuficiente para «%s»: se solicitaron %d unidades y solo quedan %d.',
            $producto->nombre,
            $solicitado,
            $producto->stock,
        ));
    }
}
