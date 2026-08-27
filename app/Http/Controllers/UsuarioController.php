<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\UsuarioRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    public function index(): View
    {
        $usuarios = User::query()
            // Solo las ventas vigentes: contar las anuladas daría una cifra
            // engañosa junto al nombre del vendedor.
            ->withCount(['ventas as ventas_count' => fn ($query) => $query->completadas()])
            ->orderBy('name')
            ->paginate(15);

        return view('usuarios.index', compact('usuarios'));
    }

    public function create(): View
    {
        return view('usuarios.create', ['roles' => Role::cases()]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        User::create([
            ...$request->safe()->only(['name', 'email', 'role']),
            'password' => $request->validated('password'),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(User $usuario): View
    {
        return view('usuarios.edit', [
            'usuario' => $usuario,
            'roles' => Role::cases(),
        ]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->safe()->only(['name', 'email', 'role']);

        // Un administrador no puede quitarse a sí mismo el rol: se quedaría
        // sin acceso a la administración y podría dejar el sistema sin admins.
        if ($usuario->is($request->user()) && $datos['role'] !== Role::Admin->value) {
            return back()->withInput()
                ->with('error', 'No puedes quitarte a ti mismo el rol de administrador.');
        }

        if (filled($request->validated('password'))) {
            $datos['password'] = $request->validated('password');
        }

        $rolAnterior = $usuario->role;

        $usuario->update($datos);

        if ($usuario->role !== $rolAnterior) {
            Log::warning('Rol de usuario modificado', [
                'usuario_id' => $usuario->id,
                'de' => $rolAnterior->value,
                'a' => $usuario->role->value,
                'modificado_por' => $request->user()->id,
            ]);
        }

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->is($request->user())) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        if ($usuario->ventas()->exists()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No se puede eliminar un usuario con ventas registradas.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
