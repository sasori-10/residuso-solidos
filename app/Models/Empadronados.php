<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empadronados extends Model
{
    protected $table = 'empadronados';
    
    // Define the fillable attributes if needed
    protected $fillable = [
        'codigo',
        'dni',
        'nombre',
        'direccion',
        'celular',
        'zona_id',
        'sector_id',
        'tipo_empadronado_id',
        'tipo_residuos',
        'horario_inicio',
        'horario_fin',
        'dias_recoleccion',
        'n_habitantes', // New attribute for number of inhabitants
        'codigo_ruta',
        'placa',
        'nombre_establecimiento',
        'tipo_establecimiento',
        'tipo_empadronado_mercado',
        'n_puesto_mercado',
        'nombre_institucion',
        'tipo_institucion',
        // Add other attributes as necessary
    ];

    // Relación: Un empadronado pertenece a una zona
    public function zona()
    {
        return $this->belongsTo(Zonas::class, 'zona_id');
    }

    // Relación: Un empadronado pertenece a un sector
    public function sector()
    {
        return $this->belongsTo(Sectores::class, 'sector_id');
    }

    // Relación: Un empadronado pertenece a un tipo de empadronado
    public function tipoEmpadronado()
    {
        return $this->belongsTo(TipoEmpadronados::class, 'tipo_empadronado_id');
    }
}
