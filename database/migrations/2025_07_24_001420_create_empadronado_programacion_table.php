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
        Schema::create('empadronado_programacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empadronado_id');
            $table->unsignedBigInteger('programacion_id');
            $table->boolean('completado')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empadronado_programacion');
    }
};
