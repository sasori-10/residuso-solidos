<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zonas extends Model
{
    protected $table = 'zonas';

    // Define the fillable attributes if needed
    protected $fillable = [
        'nombre',
        // Add other attributes as necessary
    ];

    // Relación: Una zona tiene muchos sectores
    public function sectores()
    {
        return $this->hasMany(Sectores::class, 'zona_id');
    }
}
