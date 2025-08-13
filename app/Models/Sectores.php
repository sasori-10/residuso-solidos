<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sectores extends Model
{
    protected $table = 'sectores';

    // Define the fillable attributes if needed
    protected $fillable = [
        'nombre',
        'zona_id',
        // Add other attributes as necessary
    ];

    // Relación: Un sector pertenece a una zona
    public function zona()
    {
        return $this->belongsTo(Zonas::class, 'zona_id');
    }
}
