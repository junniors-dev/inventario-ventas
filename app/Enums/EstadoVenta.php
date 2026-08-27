<?php

namespace App\Enums;

enum EstadoVenta: string
{
    case Completada = 'completada';
    case Anulada = 'anulada';

    /**
     * Etiqueta legible para mostrar en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Completada => 'Completada',
            self::Anulada => 'Anulada',
        };
    }
}
