<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Vendedor = 'vendedor';

    /**
     * Etiqueta legible para mostrar en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Vendedor => 'Vendedor',
        };
    }
}
