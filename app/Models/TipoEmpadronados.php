<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEmpadronados extends Model
{
    protected $table = 'tipo_empadronados';
    // Define the fillable attributes if needed
    protected $fillable = [
        'nombre',
    ];

    // Relación: Un tipo de empadronado tiene muchos empadronados
    public function empadronados()
    {
        return $this->hasMany(Empadronados::class, 'tipo_empadronado_id');
    }
}
