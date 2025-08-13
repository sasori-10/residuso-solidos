<?php

namespace App\Http\Controllers;

use App\Models\Zonas;
use App\Models\Sectores;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ZonasSectoresController extends Controller
{
    public function index()
    {
        $zonas = Zonas::with('sectores')->get()->map(function ($zona) {
            return [
                'id' => $zona->id,
                'nombre' => $zona->nombre,
                'sectores_count' => $zona->sectores->count(),
                'sectores' => $zona->sectores->map(function ($sector) {
                    return [
                        'id' => $sector->id,
                        'nombre' => $sector->nombre,
                        'zona_id' => $sector->zona_id,
                        'zona_nombre' => $sector->zona->nombre ?? '',
                    ];
                }),
            ];
        });

        $sectores = Sectores::with('zona')->get()->map(function ($sector) {
            return [
                'id' => $sector->id,
                'nombre' => $sector->nombre,
                'zona_id' => $sector->zona_id,
                'zona_nombre' => $sector->zona->nombre ?? '',
            ];
        });

        $zonasOptions = Zonas::pluck('nombre', 'id');

        return Inertia::render('ZonasSectores/index', [
            'zonas' => $zonas,
            'sectores' => $sectores,
            'zonasOptions' => $zonasOptions,
        ]);
    }

    // CRUD para Zonas
    public function storeZona(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:zonas,nombre',
        ]);

        Zonas::create($data);
        
        return redirect()->route('zonas-sectores.index')->with('success', 'Zona creada correctamente');
    }

    public function updateZona(Request $request, Zonas $zona)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:zonas,nombre,' . $zona->id,
        ]);

        $zona->update($data);
        
        return redirect()->route('zonas-sectores.index')->with('success', 'Zona actualizada correctamente');
    }

    public function destroyZona(Zonas $zona)
    {
        // Verificar si la zona tiene sectores
        if ($zona->sectores()->count() > 0) {
            return redirect()->route('zonas-sectores.index')->with('error', 'No se puede eliminar la zona porque tiene sectores asignados');
        }

        $zona->delete();
        return redirect()->route('zonas-sectores.index')->with('success', 'Zona eliminada correctamente');
    }

    // CRUD para Sectores
    public function storeSector(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'zona_id' => 'required|exists:zonas,id',
        ]);

        Sectores::create($data);
        
        return redirect()->route('zonas-sectores.index')->with('success', 'Sector creado correctamente');
    }

    public function updateSector(Request $request, Sectores $sector)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100',
            'zona_id' => 'required|exists:zonas,id',
        ]);

        $sector->update($data);
        
        return redirect()->route('zonas-sectores.index')->with('success', 'Sector actualizado correctamente');
    }

    public function destroySector(Sectores $sector)
    {
        $sector->delete();
        return redirect()->route('zonas-sectores.index')->with('success', 'Sector eliminado correctamente');
    }

    public function getStats()
    {
        $stats = Zonas::withCount('sectores')->get()->map(function ($zona) {
            return [
                'nombre' => $zona->nombre,
                'sectores_count' => $zona->sectores_count,
            ];
        });

        return response()->json($stats);
    }
}
