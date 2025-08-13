<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recoleccion_evidencia', function (Blueprint $table) {
            $table->id();
            // Relaciona esta evidencia con la tarea programada
            $table->foreignId('programacion_id')->constrained('programacion')->onDelete('cascade');
            // También relaciona con el empadronado que se atendió
            $table->foreignId('empadronado_id')->constrained('empadronados');
            // Ruta de la foto que evidencia la carga completada
            $table->string('ruta_foto');
            // Campo para marcar que se completó la tarea
            $table->boolean('completado')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recoleccion_evidencia');
    }
};
