<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UsuariosController extends Controller
{
    public function index()
    {
        $users = User::with('roles')->get()->map(function ($user) {
            return [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->roles->pluck('name')->first() ?? '',
            ];
        });
        // Si el usuario autenticado es supervisor, solo mostrar rol "user" para asignar
        $auth = auth()->user();
        $allRoles = Role::pluck('name');
        if ($auth && $auth->hasRole('supervisor')) {
            $roles = $allRoles->filter(fn($r) => strtolower($r) === 'user')->values();
        } else {
            $roles = $allRoles;
        }
        return Inertia::render('Users/index', [
            'users' => $users,
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string|exists:roles,name',
        ]);
        // Regla extra: si quien crea es supervisor, forzar rol 'user'
        $creator = $request->user();
        if ($creator && $creator->hasRole('supervisor')) {
            $data['role'] = 'user';
        }
        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);
        $user->syncRoles([$data['role']]);
        
        return redirect()->route('users.index')->with('success', 'Usuario creado correctamente');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|string|exists:roles,name',
        ]);
        // Regla extra: supervisor solo puede cambiar a 'user'
        $editor = $request->user();
        if ($editor && $editor->hasRole('supervisor')) {
            $data['role'] = 'user';
        }
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        $user->syncRoles([$data['role']]);
        
        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        if (!auth()->user()->can('edit.recoleccion')) {
            abort(403, 'No autorizado.');
        }
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado correctamente');
    }

    public function getStats()
    {
        $stats = User::role(['Admin', 'User', 'supervisor'])->get()->groupBy('role')->map(function ($group, $role) {
            return [
                'role' => $role,
                'count' => $group->count(),
            ];
        })->values();

        return response()->json($stats);
    }
}
