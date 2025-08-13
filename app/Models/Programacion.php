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
        'dias_recoleccion',
        'horario_inicio',
        'horario_fin',
        'completado',
        // Add other attributes as necessary
    ];

  
}
