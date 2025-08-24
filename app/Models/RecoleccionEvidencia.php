<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecoleccionEvidencia extends Model
{
    use HasFactory;

    protected $table = 'recoleccion_evidencia';

    protected $fillable = [
        'programacion_id',
        'empadronado_id',
        'ruta_foto',
        'completado',
        'estado',
        'comentario',
    ];

    public function programacion()
    {
        return $this->belongsTo(Programacion::class);
    }

    public function empadronado()
    {
        return $this->belongsTo(Empadronados::class);
    }
}