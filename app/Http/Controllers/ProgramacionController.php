<?php

namespace App\Http\Controllers;

use App\Models\Programacion;
use App\Models\User;
use App\Models\Zona;
use App\Models\Sector;
use App\Models\Zonas;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;
use App\Models\Empadronados; // + añadido
use App\Models\RecoleccionEvidencia; // + añadido

class ProgramacionController extends Controller
{
    public function index()
    {
        // Detecta el campo de rol o la relación de roles
        $userQuery = User::select('id', 'name');

        if (Schema::hasColumn('users', 'role_id')) {
            $userQuery->where('role_id', 3);
        } elseif (Schema::hasColumn('users', 'rol_id')) {
            $userQuery->where('rol_id', 3);
        } elseif (Schema::hasColumn('users', 'role')) {
            $userQuery->where('role', 3);
        } elseif (Schema::hasColumn('users', 'rol')) {
            $userQuery->where('rol', 3);
        } elseif (method_exists(new User, 'roles')) {
            // Soporte para spatie/laravel-permission u otra relación "roles"
            $userQuery->whereHas('roles', function ($q) {
                $q->where('name', 'user')->orWhere('id', 3);
            });
        }
        $users = $userQuery->orderBy('name')->get();

        $zonas = Zonas::select('id', 'nombre')->orderBy('nombre')->get();

        // Reemplazo: construir programaciones con resumen de avance
        $programaciones = Programacion::with([
            'user:id,name',
            'zona:id,nombre',
            'sector:id,nombre',
        ])->orderByDesc('id')->get()
        ->map(function ($p) {
            // Empadronados de la misma zona/sector
            $emps = Empadronados::select('id', 'nombre', 'direccion')
                ->where('zona_id', $p->zona_id)
                ->where('sector_id', $p->sector_id)
                ->orderBy('nombre')
                ->get();

            // Evidencias registradas para esta programación
            $evidencias = RecoleccionEvidencia::where('programacion_id', $p->id)
                ->whereIn('empadronado_id', $emps->pluck('id'))
                ->get()
                ->keyBy('empadronado_id');

            $completedList = $emps->filter(fn ($e) => $evidencias->has($e->id))->pluck('nombre')->values();
            $pendingList = $emps->filter(fn ($e) => !$evidencias->has($e->id))->pluck('nombre')->values();

            $total = $emps->count();
            $completed = $completedList->count();
            $pending = $total - $completed;
            $percent = $total ? round(($completed / $total) * 100) : 0;
            $lastAt = optional($evidencias->max('created_at'))->toDateTimeString();

            // Devolver estructura simplificada + progreso
            return [
                'id' => $p->id,
                'user_id' => $p->user_id,
                'zona_id' => $p->zona_id,
                'sector_id' => $p->sector_id,
                'dias' => $p->dias,
                'hora_inicio' => $p->hora_inicio,
                'hora_fin' => $p->hora_fin,
                'descripcion' => $p->descripcion,
                'user' => ['id' => $p->user->id ?? null, 'name' => $p->user->name ?? null],
                'zona' => ['id' => $p->zona->id ?? null, 'nombre' => $p->zona->nombre ?? null],
                'sector' => ['id' => $p->sector->id ?? null, 'nombre' => $p->sector->nombre ?? null],
                'progress' => [
                    'total' => $total,
                    'completed' => $completed,
                    'pending' => $pending,
                    'percent' => $percent,
                    'lastAt' => $lastAt,
                    'pendingList' => $pendingList,
                    'completedList' => $completedList,
                ],
            ];
        });

        return Inertia::render('Programacion/index', [
            'users' => $users,
            'zonas' => $zonas,
            'programaciones' => $programaciones,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'zona_id' => ['required', 'exists:zonas,id'],
            'sector_id' => ['required', 'exists:sectores,id'],
            'dias' => ['required', 'array', 'min:1'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        Programacion::create($data);

        return back()->with('success', 'Programación creada.');
    }

    public function update(Request $request, Programacion $programacion)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'zona_id' => ['required', 'exists:zonas,id'],
            'sector_id' => ['required', 'exists:sectores,id'],
            'dias' => ['required', 'array', 'min:1'],
            'hora_inicio' => ['required', 'date_format:H:i'],
            'hora_fin' => ['required', 'date_format:H:i', 'after:hora_inicio'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $programacion->update($data);

        return back()->with('success', 'Programación actualizada.');
    }

    public function destroy(Programacion $programacion)
    {
        // Verificación adicional de permiso (defensa en profundidad)
        if (!auth()->user() || !auth()->user()->can('edit.recoleccion')) {
            abort(403, 'No autorizado.');
        }
        $programacion->delete();

        return back()->with('success', 'Programación eliminada.');
    }
}
