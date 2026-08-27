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
}
