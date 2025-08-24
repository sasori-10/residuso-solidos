<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmpadronadoProgramacion extends Model
{
    protected $table = 'empadronado_programacion';
    protected $fillable = [
        'empadronado_id',
        'programacion_id',
        'completado',
    ];
}
