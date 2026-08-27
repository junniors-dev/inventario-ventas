<?php

namespace App\Policies;

use App\Enums\EstadoVenta;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Auth\Access\Response;

class VentaPolicy
{
    /**
     * Ver el detalle de una venta.
     *
     * El admin ve todas; el vendedor solo las que él registró.
     */
    public function view(User $user, Venta $venta): bool
    {
        return $user->isAdmin() || $venta->user_id === $user->id;
    }

    /**
     * Anular una venta.
     *
     * Solo el admin puede anular, y únicamente si la venta sigue completada.
     * A diferencia del middleware de rol, esta decisión depende del estado
     * del registro concreto, por eso vive en una policy.
     */
    public function anular(User $user, Venta $venta): Response
    {
        if (! $user->isAdmin()) {
            return Response::deny('Solo un administrador puede anular ventas.');
        }

        if ($venta->estado === EstadoVenta::Anulada) {
            return Response::deny('Esta venta ya fue anulada.');
        }

        return Response::allow();
    }
}
