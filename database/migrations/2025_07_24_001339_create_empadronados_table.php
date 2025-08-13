<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('empadronados', function (Blueprint $table) {
                $table->id();
                $table->char('codigo', 4);
                $table->string('dni')->unique();
                $table->string('nombre');
                $table->string('direccion');
                $table->string('celular')->nullable();
                $table->foreignId('zona_id')->constrained('zonas')->onDelete('cascade');
                $table->foreignId('sector_id')->constrained('sectores')->onDelete('cascade');
                $table->foreignId('tipo_empadronado_id')->constrained('tipo_empadronados')->onDelete('cascade'); // ← AGREGADO
                $table->string('tipo_residuos');
                $table->time('horario_inicio');
                $table->time('horario_fin');
                $table->json('dias_recoleccion'); 
                $table->integer('n_habitantes')->nullable();
                $table->string('codigo_ruta', 50)->nullable();
                $table->string('placa', 20)->nullable();
                $table->string('nombre_establecimiento')->nullable();
                $table->string('tipo_establecimiento')->nullable(); 
                $table->string('tipo_empadronado_mercado')->nullable();
                $table->string('n_puesto_mercado')->nullable();
                $table->string('nombre_institucion')->nullable();
                $table->string('tipo_institucion')->nullable();
                $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empadronados');
    }
};
