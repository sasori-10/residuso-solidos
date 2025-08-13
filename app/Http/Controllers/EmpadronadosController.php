<?php

namespace App\Http\Controllers;

use App\Models\Empadronados;
use App\Models\TipoEmpadronados;
use App\Models\Zonas;
use App\Models\Sectores;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmpadronadosController extends Controller
{
    public function index(Request $request)
    {
        $tiposEmpadronados = TipoEmpadronados::with('empadronados')->get()->map(function ($tipo) {
            return [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'empadronados_count' => $tipo->empadronados->count(),
            ];
        });

        // Obtener parámetros de filtros y paginación
        $perPage = $request->input('per_page', 10);
        $sectorFilter = $request->input('sector_filter');
        $tipoFilter = $request->input('tipo_filter');
        $search = $request->input('search');

        // Query base para empadronados
        $query = Empadronados::with(['zona', 'sector', 'tipoEmpadronado']);

        // Aplicar filtros
        if ($sectorFilter) {
            $query->where('sector_id', $sectorFilter);
        }

        if ($tipoFilter) {
            $query->where('tipo_empadronado_id', $tipoFilter);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('dni', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('direccion', 'like', "%{$search}%");
            });
        }

        // Obtener datos paginados
        $empadronadosPaginated = $query->paginate($perPage);

        // Transformar datos
        $empadronados = $empadronadosPaginated->map(function ($empadronado) {
            return [
                'id' => $empadronado->id,
                'codigo' => $empadronado->codigo,
                'dni' => $empadronado->dni,
                'nombre' => $empadronado->nombre,
                'direccion' => $empadronado->direccion,
                'celular' => $empadronado->celular,
                'zona_id' => $empadronado->zona_id,
                'zona_nombre' => $empadronado->zona->nombre ?? '',
                'sector_id' => $empadronado->sector_id,
                'sector_nombre' => $empadronado->sector->nombre ?? '',
                'tipo_empadronado' => $empadronado->tipo_empadronado_id,
                'tipo_empadronado_nombre' => $empadronado->tipoEmpadronado->nombre ?? '',
                'tipo_residuos' => $empadronado->tipo_residuos,
                'horario_inicio' => $empadronado->horario_inicio,
                'horario_fin' => $empadronado->horario_fin,
                'dias_recoleccion' => $empadronado->dias_recoleccion,
                // Campos adicionales dinámicos
                'n_habitantes' => $empadronado->n_habitantes,
                'codigo_ruta' => $empadronado->codigo_ruta,
                'placa' => $empadronado->placa,
                'nombre_establecimiento' => $empadronado->nombre_establecimiento,
                'tipo_establecimiento' => $empadronado->tipo_establecimiento,
                'tipo_empadronado_mercado' => $empadronado->tipo_empadronado_mercado,
                'n_puesto_mercado' => $empadronado->n_puesto_mercado,
                'nombre_institucion' => $empadronado->nombre_institucion,
                'tipo_institucion' => $empadronado->tipo_institucion,
            ];
        });

        $tiposOptions = TipoEmpadronados::pluck('nombre', 'id');
        $zonasOptions = Zonas::pluck('nombre', 'id');
        $sectoresOptions = Sectores::with('zona')->get()->groupBy('zona_id')->map(function ($sectores) {
            return $sectores->pluck('nombre', 'id');
        });

        // Opciones para filtros
        $sectoresForFilter = Sectores::pluck('nombre', 'id');
        $tiposForFilter = TipoEmpadronados::pluck('nombre', 'id');

        return Inertia::render('Empadronados/index', [
            'tiposEmpadronados' => $tiposEmpadronados,
            'empadronados' => $empadronados,
            'pagination' => [
                'current_page' => $empadronadosPaginated->currentPage(),
                'last_page' => $empadronadosPaginated->lastPage(),
                'per_page' => $empadronadosPaginated->perPage(),
                'total' => $empadronadosPaginated->total(),
                'from' => $empadronadosPaginated->firstItem(),
                'to' => $empadronadosPaginated->lastItem(),
                'links' => $empadronadosPaginated->links()->elements[0] ?? [],
            ],
            'filters' => [
                'sector_filter' => $sectorFilter,
                'tipo_filter' => $tipoFilter,
                'search' => $search,
                'per_page' => $perPage,
            ],
            'tiposOptions' => $tiposOptions,
            'zonasOptions' => $zonasOptions,
            'sectoresOptions' => $sectoresOptions,
            'sectoresForFilter' => $sectoresForFilter,
            'tiposForFilter' => $tiposForFilter,
        ]);
    }

    // CRUD para Tipos de Empadronados
    public function storeTipo(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:tipo_empadronados,nombre',
        ]);

        TipoEmpadronados::create($data);
        
        return redirect()->route('empadronados.index')->with('success', 'Tipo de empadronado creado correctamente');
    }

    public function updateTipo(Request $request, TipoEmpadronados $tipo)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:100|unique:tipo_empadronados,nombre,' . $tipo->id,
        ]);

        $tipo->update($data);
        
        return redirect()->route('empadronados.index')->with('success', 'Tipo de empadronado actualizado correctamente');
    }

    public function destroyTipo(TipoEmpadronados $tipo)
    {
        // Verificar si el tipo tiene empadronados
        if ($tipo->empadronados()->count() > 0) {
            return redirect()->route('empadronados.index')->with('error', 'No se puede eliminar el tipo porque tiene empadronados asignados');
        }

        $tipo->delete();
        return redirect()->route('empadronados.index')->with('success', 'Tipo de empadronado eliminado correctamente');
    }

    // CRUD para Empadronados
    public function store(Request $request)
    {
        // Validaciones base
        $baseRules = [
            'codigo' => 'required|string|max:100|unique:empadronados,codigo',
            'dni' => 'required|string|max:8|unique:empadronados,dni',
            'nombre' => 'required|string|max:100',
            'direccion' => 'required|string|max:255',
            'celular' => 'nullable|string|max:15',
            'zona_id' => 'required|exists:zonas,id',
            'sector_id' => 'required|exists:sectores,id',
            'tipo_empadronado' => 'required|exists:tipo_empadronados,id',
            'tipo_residuos' => 'required|string|max:100',
        ];

        // Validaciones específicas según el tipo de empadronado
        $tipoId = $request->input('tipo_empadronado');
        $additionalRules = $this->getValidationRulesByType($tipoId);
        
        $rules = array_merge($baseRules, $additionalRules);
        $data = $request->validate($rules);

        $data['tipo_empadronado_id'] = $data['tipo_empadronado'];
        unset($data['tipo_empadronado']);
        
        // Generar código automáticamente para todos los tipos
        $data['codigo'] = $this->generateCodigo($data['tipo_empadronado_id']);
        
        Empadronados::create($data);
        
        return redirect()->route('empadronados.index')->with('success', 'Empadronado creado correctamente');
    }

    public function update(Request $request, Empadronados $empadronado)
    {
        // Validaciones base
        $baseRules = [
            'codigo' => 'required|string|max:100|unique:empadronados,codigo,' . $empadronado->id,
            'dni' => 'required|string|max:8|unique:empadronados,dni,' . $empadronado->id,
            'nombre' => 'required|string|max:100',
            'direccion' => 'required|string|max:255',
            'celular' => 'nullable|string|max:15',
            'zona_id' => 'required|exists:zonas,id',
            'sector_id' => 'required|exists:sectores,id',
            'tipo_empadronado' => 'required|exists:tipo_empadronados,id',
            'tipo_residuos' => 'required|string|max:100',
        ];

        // Validaciones específicas según el tipo de empadronado
        $tipoId = $request->input('tipo_empadronado');
        $additionalRules = $this->getValidationRulesByType($tipoId);
        
        $rules = array_merge($baseRules, $additionalRules);
        $data = $request->validate($rules);

        $data['tipo_empadronado_id'] = $data['tipo_empadronado'];
        unset($data['tipo_empadronado']);

        $empadronado->update($data);
        
        return redirect()->route('empadronados.index')->with('success', 'Empadronado actualizado correctamente');
    }

    public function destroy(Empadronados $empadronado)
    {
        $empadronado->delete();
        return redirect()->route('empadronados.index')->with('success', 'Empadronado eliminado correctamente');
    }

    // API para obtener sectores por zona
    public function getSectoresByZona($zonaId)
    {
        $sectores = Sectores::where('zona_id', $zonaId)->pluck('nombre', 'id');
        return response()->json($sectores);
    }

    // API para obtener configuración de campos según tipo de empadronado
    public function getFieldConfigByType($tipoId)
    {
        $config = $this->getFieldConfigurationByType($tipoId);
        return response()->json($config);
    }

    /**
     * Obtiene las reglas de validación específicas según el tipo de empadronado
     */
    private function getValidationRulesByType($tipoId)
    {
        $rules = [];
        
        switch ($tipoId) {
            case 1: // VIVIENDAS
                $rules = array_merge($rules, [
                    'n_habitantes' => 'required|integer|min:1',
                    'codigo_ruta' => 'required|string|max:50',
                    'placa' => 'required|string|max:20',
                    'horario_inicio' => 'required|date_format:H:i',
                    'horario_fin' => 'required|date_format:H:i|after:horario_inicio',
                    'dias_recoleccion' => 'required|string',
                ]);
                break;
                
            case 2: // COMERCIO
                $rules = array_merge($rules, [
                    'nombre_establecimiento' => 'required|string|max:255',
                    'tipo_establecimiento' => 'required|string|max:100',
                    'codigo_ruta' => 'required|string|max:50',
                    'placa' => 'required|string|max:20',
                    'horario_inicio' => 'required|date_format:H:i',
                    'horario_fin' => 'required|date_format:H:i|after:horario_inicio',
                    'dias_recoleccion' => 'required|string',
                ]);
                break;
                
            case 3: // MERCADOS
                $rules = array_merge($rules, [
                    'nombre_establecimiento' => 'required|string|max:255', // Nombre del mercado
                    'tipo_empadronado_mercado' => 'required|string|max:100',
                    'n_puesto_mercado' => 'required|string|max:100',
                ]);
                break;
                
            case 4: // VIVIENDAS-ORG
                // Solo campos básicos, sin campos adicionales específicos
                break;
                
            case 5: // INSTITUCIONES EDUCATIVAS
                $rules = array_merge($rules, [
                    'nombre_institucion' => 'required|string|max:255',
                    'tipo_institucion' => 'required|string|max:100',
                    // No requiere n_puesto_mercado para instituciones educativas
                ]);
                break;
                
            case 6: // INSTITUCIONES PUB Y PRIV
                $rules = array_merge($rules, [
                    'nombre_institucion' => 'required|string|max:255',
                    'tipo_institucion' => 'required|string|max:100',
                    // No requiere n_puesto_mercado para instituciones públicas y privadas
                ]);
                break;
                
            case 7: // OTROS
                $rules = array_merge($rules, [
                    'nombre_establecimiento' => 'required|string|max:255', // Nombre del local
                    'tipo_establecimiento' => 'required|string|max:100',
                ]);
                break;
        }
        
        return $rules;
    }

    /**
     * Obtiene la configuración de campos para mostrar en el frontend
     */
    private function getFieldConfigurationByType($tipoId)
    {
        $config = [
            'showHorarios' => false,
            'showHabitantes' => false,
            'showCodigoRuta' => false,
            'showPlaca' => false,
            'showNombreEstablecimiento' => false,
            'showTipoEstablecimiento' => false,
            'showNombreInstitucion' => false,
            'showTipoInstitucion' => false,
            'showNumeroPuestos' => false,
            'showTipoMercado' => false,
            'labels' => []
        ];
        
        switch ($tipoId) {
            case 1: // VIVIENDAS
                $config['showHorarios'] = true;
                $config['showHabitantes'] = true;
                $config['showCodigoRuta'] = true;
                $config['showPlaca'] = true;
                $config['labels'] = [
                    'establecimiento' => 'Vivienda',
                    'representante' => 'Nombre y Apellido del representante'
                ];
                break;
                
            case 2: // COMERCIO
                $config['showHorarios'] = true;
                $config['showCodigoRuta'] = true;
                $config['showPlaca'] = true;
                $config['showNombreEstablecimiento'] = true;
                $config['showTipoEstablecimiento'] = true;
                $config['labels'] = [
                    'establecimiento' => 'Nombre del establecimiento comercial',
                    'tipo' => 'Tipo de establecimiento comercial',
                    'representante' => 'Nombre y Apellido del representante'
                ];
                break;
                
            case 3: // MERCADOS
                $config['showNombreEstablecimiento'] = true;
                $config['showTipoMercado'] = true;
                $config['showNumeroPuestos'] = true;
                $config['labels'] = [
                    'establecimiento' => 'Nombre del mercado',
                    'tipo' => 'Tipo',
                    'puestos' => 'N° de puestos que participan',
                    'representante' => 'Nombre y Apellido del representante'
                ];
                break;
                
            case 4: // VIVIENDAS-ORG
                $config['labels'] = [
                    'representante' => 'Nombre y Apellido del representante'
                ];
                break;
                
            case 5: // INSTITUCIONES EDUCATIVAS
                $config['showNombreInstitucion'] = true;
                $config['showTipoInstitucion'] = true;
                $config['showNumeroPuestos'] = false; // No mostrar número de puestos para educativas
                $config['labels'] = [
                    'institucion' => 'Nombre de la institución',
                    'tipo' => 'Tipo de institución',
                    'representante' => 'Nombre y Apellido del representante'
                ];
                break;
                
            case 6: // INSTITUCIONES PUB Y PRIV
                $config['showNombreInstitucion'] = true;
                $config['showTipoInstitucion'] = true;
                $config['showNumeroPuestos'] = false; // No mostrar número de puestos
                $config['labels'] = [
                    'institucion' => 'Nombre de la institución',
                    'tipo' => 'Tipo de institución',
                    'representante' => 'Nombre y Apellido del representante'
                ];
                break;
                
            case 7: // OTROS
                $config['showNombreEstablecimiento'] = true;
                $config['showTipoEstablecimiento'] = true;
                $config['showNumeroPuestos'] = false; // No mostrar número de puestos
                $config['labels'] = [
                    'establecimiento' => 'Nombre del local',
                    'tipo' => 'Tipo',
                    'representante' => 'Nombre y Apellido del representante'
                ];
                break;
        }
        
        return $config;
    }

    /**
     * Genera el código automáticamente para todos los tipos
     */
    private function generateCodigo($tipoId)
    {
        // Obtener el prefijo según el tipo
        $prefijo = $this->getPrefijoByTipo($tipoId);
        
        // Obtener el último número usado para este tipo
        $ultimoCodigo = Empadronados::where('tipo_empadronado_id', $tipoId)
            ->where('codigo', 'LIKE', $prefijo . '%')
            ->orderByRaw('CAST(SUBSTRING(codigo, 2) AS UNSIGNED) DESC')
            ->value('codigo');
        
        if ($ultimoCodigo) {
            // Extraer el número del código (quitar el prefijo)
            $numero = intval(substr($ultimoCodigo, 1));
            $siguienteNumero = $numero + 1;
        } else {
            // Es el primer código para este tipo
            $siguienteNumero = 1;
        }
        
        // Formatear con ceros a la izquierda (3 dígitos)
        return $prefijo . str_pad($siguienteNumero, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene el prefijo del código según el tipo de empadronado
     */
    private function getPrefijoByTipo($tipoId)
    {
        $prefijos = [
            1 => 'V', // VIVIENDAS
            2 => 'C', // COMERCIO
            3 => 'M', // MERCADOS
            4 => 'O', // VIVIENDAS-ORG (Organizadas)
            5 => 'E', // INSTITUCIONES EDUCATIVAS
            6 => 'I', // INSTITUCIONES PUB Y PRIV
            7 => 'X', // OTROS
        ];
        
        return $prefijos[$tipoId] ?? 'G'; // G = General (fallback)
    }

    public function getStats()
    {
        $stats = TipoEmpadronados::withCount('empadronados')->get()->map(function ($tipo) {
            return [
                'nombre' => $tipo->nombre,
                'empadronados_count' => $tipo->empadronados_count,
            ];
        });

        return response()->json($stats);
    }

    public function getTipoResiduosStats()
    {
        $stats = Empadronados::select('tipo_residuos', \DB::raw('count(*) as count'))
            ->groupBy('tipo_residuos')
            ->get();

        return response()->json($stats);
    }
}
