<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('codigo', 20)->unique();   // EXENTO, ISV15, ISV18
            $table->string('nombre');                 // "ISV 15%"
            $table->decimal('tasa', 5, 2);            // 15.00, 18.00, 0.00 — editable
            $table->boolean('activo')->default(true);
            $table->boolean('es_default')->default(false); // tasa por defecto para productos nuevos
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('impuestos');
    }
};