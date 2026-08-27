<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UsuarioRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($usuario),
            ],
            'role' => ['required', Rule::enum(Role::class)],
            // Al crear la contraseña es obligatoria; al editar solo se valida
            // si el administrador escribe una nueva.
            'password' => [
                $usuario ? 'nullable' : 'required',
                'confirmed',
                Password::defaults(),
            ],
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
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Ya existe un usuario con ese correo.',
            'role.required' => 'Debes asignar un rol.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
