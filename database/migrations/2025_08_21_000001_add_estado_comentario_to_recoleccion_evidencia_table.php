<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('recoleccion_evidencia', function (Blueprint $table) {
            $table->string('estado')->default('completado');
            $table->text('comentario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recoleccion_evidencia', function (Blueprint $table) {
            $table->dropColumn(['estado', 'comentario']);
        });
    }
};
