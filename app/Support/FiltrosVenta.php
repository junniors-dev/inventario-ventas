<?php

namespace App\Support;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use Illuminate\Http\Request;

class FiltrosVenta
{
    /**
     * Campos que el historial de ventas acepta como filtro.
     *
     * @var array<int, string>
     */
    public const CAMPOS = ['buscar', 'desde', 'hasta', 'vendedor', 'metodo_pago', 'estado'];

    /**
     * Traduce los parámetros de la petición a filtros tipados.
     *
     * Un valor inválido (una fecha imposible, un método de pago inexistente)
     * se descarta en lugar de romper la consulta.
     *
     * @return array<string, mixed>
     */
    public static function desdePeticion(Request $request): array
    {
        return [
            'buscar' => $request->string('buscar')->trim()->value() ?: null,
            'desde' => rescue(fn () => $request->date('desde'), null, report: false),
            'hasta' => rescue(fn () => $request->date('hasta'), null, report: false),
            'vendedor' => $request->integer('vendedor') ?: null,
            'metodo_pago' => $request->enum('metodo_pago', MetodoPago::class),
            'estado' => $request->enum('estado', EstadoVenta::class),
        ];
    }

    /**
     * ¿La petición trae algún filtro activo?
     */
    public static function hayFiltros(Request $request): bool
    {
        return collect(self::CAMPOS)->contains(fn (string $campo) => filled($request->input($campo)));
    }
}
