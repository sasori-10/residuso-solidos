<?php

namespace App\Http\Controllers;

use App\Models\Programacion;
use App\Models\RecoleccionEvidencia;
use App\Models\Empadronados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RecoleccionEvidenciaController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $programaciones = Programacion::with([
            'zona:id,nombre',
            'sector:id,nombre',
        ])->where('user_id', $userId)->orderBy('id', 'desc')->get();

        $assignments = [];

        foreach ($programaciones as $prog) {
            // Empadronados en la misma zona/sector de la programación
            $emps = Empadronados::select('id', 'nombre', 'direccion')
                ->where('zona_id', $prog->zona_id)
                ->where('sector_id', $prog->sector_id)
                ->orderBy('nombre')
                ->get();

            // Evidencias ya registradas para esta programación
            $evidencias = RecoleccionEvidencia::where('programacion_id', $prog->id)
                ->whereIn('empadronado_id', $emps->pluck('id'))
                ->get()
                ->keyBy('empadronado_id');

            $empadronados = $emps->map(function ($e) use ($evidencias) {
                $ev = $evidencias->get($e->id);

                $url = null;
                if ($ev) {
                    // Si la ruta es relativa a public/evidencias
                    if (is_string($ev->ruta_foto) && substr($ev->ruta_foto, 0, 11) === 'evidencias/') {
                        $url = asset($ev->ruta_foto);
                    } else {
                        // Compatibilidad con registros antiguos guardados en storage/public
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
        ]);
    }

    public function store(Request $request)
    {
        $userId = Auth::id();

        $data = $request->validate([
            'programacion_id' => ['required', 'exists:programacion,id'],
            'empadronado_id' => ['required', 'exists:empadronados,id'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $programacion = Programacion::where('id', $data['programacion_id'])
            ->where('user_id', $userId)
            ->firstOrFail();

        // Validar zona/sector del empadronado
        $emp = Empadronados::select('id', 'zona_id', 'sector_id')->findOrFail($data['empadronado_id']);
        if ($emp->zona_id !== $programacion->zona_id || $emp->sector_id !== $programacion->sector_id) {
            abort(403, 'Empadronado no pertenece a esta asignación.');
        }

        // Guardar en public/evidencias
        $dir = public_path('evidencias');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $ext = $request->file('foto')->getClientOriginalExtension();
        $filename = uniqid('ev_').'_'.time().'.'.$ext;
        $request->file('foto')->move($dir, $filename);
        $relativePath = 'evidencias/'.$filename;

        RecoleccionEvidencia::create([
            'programacion_id' => $programacion->id,
            'empadronado_id' => $emp->id,
            'ruta_foto' => $relativePath, // guardamos ruta relativa a public
            'completado' => true,
        ]);

        return back()->with('success', 'Evidencia registrada');
    }
}
