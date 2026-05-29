<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('puntos_emision', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('establecimiento_id')->constrained('establecimientos');
            $table->string('codigo', 3);
            $table->string('nombre');                         // ej. "Caja 1"
            $table->string('emisor_tipo')->default('mayor');  // mayor | auxiliar
            $table->uuid('emisor_instance_uuid')->nullable(); // qué Auxiliar la atiende; null = Mayor
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['establecimiento_id', 'codigo']); // código único por establecimiento
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puntos_emision');
    }
};