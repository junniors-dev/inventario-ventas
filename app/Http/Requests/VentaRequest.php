<?php

namespace App\Http\Requests;

use App\Enums\MetodoPago;
use App\Models\Producto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VentaRequest extends FormRequest
{
    /**
     * Cualquier usuario autenticado (admin o vendedor) puede registrar ventas.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'metodo_pago' => ['required', Rule::enum(MetodoPago::class)],
            'cliente_nombre' => ['nullable', 'string', 'max:255'],
            'cliente_documento' => ['nullable', 'string', 'max:20'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.producto_id' => ['required', 'integer', Rule::exists('productos', 'id')->whereNull('deleted_at')],
            'lineas.*.cantidad' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * Validación que depende de varios campos y del estado de la base de datos.
     *
     * Es una comprobación temprana para dar un mensaje claro al usuario; la
     * garantía real contra condiciones de carrera vive en RegistrarVenta,
     * dentro de la transacción con lockForUpdate.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $lineas = collect($this->input('lineas', []));

                $solicitado = $lineas
                    ->groupBy('producto_id')
                    ->map(fn ($grupo) => $grupo->sum(fn ($l) => (int) $l['cantidad']));

                $productos = Producto::whereIn('id', $solicitado->keys())->get()->keyBy('id');

                foreach ($solicitado as $productoId => $cantidad) {
                    $producto = $productos->get((int) $productoId);

                    if ($producto !== null && $producto->stock < $cantidad) {
                        $validator->errors()->add(
                            'lineas',
                            "Stock insuficiente para «{$producto->nombre}»: quedan {$producto->stock} unidades."
                        );
                    }
                }
            },
        ];
    }

    /**
     * Mensajes de error en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lineas.required' => 'Debes agregar al menos un producto a la venta.',
            'lineas.min' => 'Debes agregar al menos un producto a la venta.',
            'metodo_pago.required' => 'Selecciona un método de pago.',
            'lineas.*.cantidad.min' => 'La cantidad debe ser al menos 1.',
        ];
    }
}
