<?php

namespace App\Models;

use App\Enums\EstadoVenta;
use App\Enums\MetodoPago;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'codigo',
        'user_id',
        'total',
        'metodo_pago',
        'estado',
        'anulada_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'metodo_pago' => MetodoPago::class,
            'estado' => EstadoVenta::class,
            'anulada_at' => 'datetime',
        ];
    }

    /**
     * Usuario (vendedor o admin) que registró la venta.
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Líneas de detalle de la venta.
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    /**
     * Solo ventas completadas (no anuladas).
     */
    #[Scope]
    protected function completadas(Builder $query): Builder
    {
        return $query->where('estado', EstadoVenta::Completada);
    }

    /**
     * Aplica los filtros del historial de ventas.
     *
     * Vive en el modelo para que el listado y la exportación compartan
     * exactamente los mismos criterios y no puedan divergir.
     *
     * @param  array{buscar?: ?string, desde?: ?Carbon, hasta?: ?Carbon, vendedor?: ?int, metodo_pago?: ?MetodoPago, estado?: ?EstadoVenta}  $filtros
     */
    #[Scope]
    protected function filtradas(Builder $query, array $filtros): Builder
    {
        return $query
            ->when($filtros['buscar'] ?? null, fn (Builder $query, string $codigo) => $query->where('codigo', 'like', "%{$codigo}%"))
            ->when($filtros['desde'] ?? null, fn (Builder $query, Carbon $desde) => $query->where('created_at', '>=', $desde->startOfDay()))
            ->when($filtros['hasta'] ?? null, fn (Builder $query, Carbon $hasta) => $query->where('created_at', '<=', $hasta->endOfDay()))
            ->when($filtros['vendedor'] ?? null, fn (Builder $query, int $id) => $query->where('user_id', $id))
            ->when($filtros['metodo_pago'] ?? null, fn (Builder $query, MetodoPago $metodo) => $query->where('metodo_pago', $metodo))
            ->when($filtros['estado'] ?? null, fn (Builder $query, EstadoVenta $estado) => $query->where('estado', $estado));
    }
}
