<?php

namespace App\Http\Controllers;

use App\Models\Programacion;
use App\Models\RecoleccionEvidencia;
use App\Models\Empadronados;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RecoleccionEvidenciaController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();
        $ownUserId = $authUser->id;

        $targetUserId = $request->query('user');
        $viewingOther = false;
        $targetUser = null;

        // Determinar si se está solicitando ver a otro usuario
        if ($targetUserId && (int)$targetUserId !== (int)$ownUserId) {
            // Requiere permisos de supervisor / gestión
            if ($authUser->can('supervisor.recoleccion') || $authUser->can('manage.recoleccion')) {
                $targetUser = User::findOrFail($targetUserId);
                // (Opcional) limitar a que solo pueda supervisar usuarios con rol 'user'
                if (method_exists($targetUser, 'hasRole') && !$targetUser->hasRole('user')) {
                    abort(403, 'No autorizado a supervisar este usuario.');
                }
                $userIdToLoad = $targetUser->id;
                $viewingOther = true;
            } else {
                abort(403, 'No autorizado');
            }
        } else {
            $userIdToLoad = $ownUserId;
        }

        $programaciones = Programacion::with([
            'zona:id,nombre',
            'sector:id,nombre',
            'empadronados:id,nombre,direccion' // pivot relation
        ])->where('user_id', $userIdToLoad)
            ->orderBy('id', 'desc')
            ->get();

        $assignments = [];

        foreach ($programaciones as $prog) {
            // Preferir empadronados asignados por pivot; si no hay, fallback por zona/sector (compatibilidad antigua)
            if ($prog->empadronados && $prog->empadronados->count()) {
                $emps = $prog->empadronados->sortBy('nombre')->values();
            } else {
                $emps = Empadronados::select('id', 'nombre', 'direccion')
                    ->where('zona_id', $prog->zona_id)
                    ->where('sector_id', $prog->sector_id)
                    ->orderBy('nombre')
                    ->get();
            }

            $evidencias = RecoleccionEvidencia::where('programacion_id', $prog->id)
                ->whereIn('empadronado_id', $emps->pluck('id'))
                ->get()
                ->keyBy('empadronado_id');

            $empadronados = $emps->map(function ($e) use ($evidencias) {
                $ev = $evidencias->get($e->id);
                $url = null;
                if ($ev) {
                    if (is_string($ev->ruta_foto) && substr($ev->ruta_foto, 0, 11) === 'evidencias/') {
                        $url = asset($ev->ruta_foto);
                    } else {
                        $url = Storage::url($ev->ruta_foto);
                    }
                }
                return [
                    'id' => $e->id,
                    'nombre' => $e->nombre,
                    'direccion' => $e->direccion,
                    'evidencia' => $ev ? [
                        'id' => $ev->id,
                        'ruta_foto' => $url,
                        'completado' => (bool) $ev->completado,
                        'estado' => $ev->estado,
                        'comentario' => $ev->comentario,
                        'created_at' => $ev->created_at,
                    ] : null,
                ];
            })->values();

            $assignments[] = [
                'programacion' => [
                    'id' => $prog->id,
                    'zona' => $prog->zona->nombre ?? null,
                    'sector' => $prog->sector->nombre ?? null,
                    'dias' => $prog->dias,
                    'hora_inicio' => $prog->hora_inicio,
                    'hora_fin' => $prog->hora_fin,
                    'descripcion' => $prog->descripcion,
                ],
                'empadronados' => $empadronados,
            ];
        }

        return Inertia::render('Recoleccion_evidencia/index', [
            'assignments' => $assignments,
            'viewingOther' => $viewingOther,
            'targetUser' => $viewingOther ? [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'email' => $targetUser->email,
            ] : null,
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();

        $data = $request->validate([
            'programacion_id' => ['required', 'exists:programacion,id'],
            'empadronado_id' => ['required', 'exists:empadronados,id'],
            'estado' => ['required', 'in:completado,no_completado,no_encontrado'],
            'comentario' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $programacion = Programacion::where('id', $data['programacion_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        // Validar zona/sector del empadronado
        $emp = Empadronados::select('id', 'zona_id', 'sector_id')->findOrFail($data['empadronado_id']);
        if ($emp->zona_id !== $programacion->zona_id || $emp->sector_id !== $programacion->sector_id) {
            abort(403, 'Empadronado no pertenece a esta asignación.');
        }

        // Validaciones adicionales según estado
        if ($data['estado'] === 'completado') {
            if (!$request->hasFile('foto')) {
                return back()->withErrors(['foto' => 'La foto es obligatoria para estado completado.']);
            }
        }
        if ($data['estado'] === 'no_encontrado') {
            if (empty($data['comentario'])) {
                return back()->withErrors(['comentario' => 'El comentario es obligatorio si la persona no fue encontrada.']);
            }
        }

        $relativePath = null;
        if ($request->hasFile('foto')) {
            $dir = public_path('evidencias');
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            $ext = $request->file('foto')->getClientOriginalExtension();
            $filename = uniqid('ev_').'_'.time().'.'.$ext;
            $request->file('foto')->move($dir, $filename);
            $relativePath = 'evidencias/'.$filename;
        }

        RecoleccionEvidencia::create([
            'programacion_id' => $programacion->id,
            'empadronado_id' => $emp->id,
            'ruta_foto' => $relativePath,
            'completado' => $data['estado'] === 'completado',
            'estado' => $data['estado'],
            'comentario' => $data['comentario'] ?? null,
        ]);

        return back()->with('success', 'Evidencia registrada');
    }
}
