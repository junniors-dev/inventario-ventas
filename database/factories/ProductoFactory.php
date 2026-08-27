<?php

namespace Database\Factories;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stockMinimo = $this->faker->numberBetween(5, 15);

        return [
            'categoria_id' => Categoria::factory(),
            'nombre' => ucfirst($this->faker->unique()->words(2, true)),
            'codigo_barras' => $this->faker->unique()->ean13(),
            'precio' => $this->faker->randomFloat(2, 1, 60),
            'stock' => $this->faker->numberBetween($stockMinimo + 1, 80),
            'stock_minimo' => $stockMinimo,
        ];
    }

    /**
     * Producto por debajo de su stock mínimo.
     */
    public function stockBajo(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => $this->faker->numberBetween(0, (int) $attributes['stock_minimo']),
        ]);
    }

    /**
     * Producto sin existencias.
     */
    public function agotado(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    /**
     * Producto sin código de barras (venta a granel, empaque propio).
     */
    public function sinCodigoBarras(): static
    {
        return $this->state(fn () => ['codigo_barras' => null]);
    }
}
