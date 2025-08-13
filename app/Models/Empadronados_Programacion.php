<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Empadronados_Programacion extends Model
{
    protected $table = 'empadronados_programacion';
    // Define the fillable attributes if needed
    protected $fillable = [
        'empadronado_id',
        'programacion_id',
        'completado',
        // Add other attributes as necessary
    ];
}
