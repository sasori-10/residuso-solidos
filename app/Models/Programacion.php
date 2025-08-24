<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programacion extends Model
{
    protected $table = 'programacion';

    // Define the fillable attributes if needed
    protected $fillable = [
        'user_id',
        'zona_id',
        'sector_id',
        'dias',
        'hora_inicio',
        'hora_fin',
        'descripcion',
    ];

    protected $casts = [
        'dias' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function zona()
    {
        return $this->belongsTo(\App\Models\Zonas::class);
    }

    public function sector()
    {
        return $this->belongsTo(\App\Models\Sectores::class);
    }

    /**
     * Empadronados asignados explícitamente mediante la tabla pivot empadronado_programacion
     */
    public function empadronados()
    {
        return $this->belongsToMany(Empadronados::class, 'empadronado_programacion', 'programacion_id', 'empadronado_id');
    }
}
