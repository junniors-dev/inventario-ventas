<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductoRequest extends FormRequest
{
    /**
     * La autorización la resuelve el middleware 'role:admin' del grupo de rutas.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categoria_id' => ['required', Rule::exists('categorias', 'id')],
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('productos', 'nombre')
                    ->ignore($this->route('producto'))
                    ->whereNull('deleted_at'),
            ],
            'codigo_barras' => [
                'nullable',
                'string',
                'max:32',
                // El índice único de la columna alcanza también a los productos
                // borrados lógicamente, así que la regla no los excluye.
                Rule::unique('productos', 'codigo_barras')->ignore($this->route('producto')),
            ],
            'precio' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
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
            'categoria_id.required' => 'Debes seleccionar una categoría.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'nombre.required' => 'El nombre del producto es obligatorio.',
            'nombre.unique' => 'Ya existe un producto con ese nombre.',
            'codigo_barras.unique' => 'Ese código de barras ya está asignado a otro producto.',
            'precio.required' => 'El precio es obligatorio.',
            'precio.gt' => 'El precio debe ser mayor que cero.',
            'stock.min' => 'El stock no puede ser negativo.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
        ];
    }
}
