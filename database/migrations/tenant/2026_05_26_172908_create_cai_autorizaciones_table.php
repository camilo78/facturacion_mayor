<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cai_autorizaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('punto_emision_id')->constrained('puntos_emision');
            $table->string('tipo_documento', 2);            // 01=factura, 03=N.crédito, 04=N.débito
            $table->string('cai')->unique();                // código CAI del SAR
            $table->unsignedInteger('rango_inicial');
            $table->unsignedInteger('rango_final');
            $table->unsignedInteger('correlativo_actual')->default(0); // último emitido
            $table->date('fecha_limite_emision');
            $table->boolean('activo')->default(true);       // control manual (reemplazo anticipado)
            $table->timestamps();

            $table->index(['punto_emision_id', 'tipo_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cai_autorizaciones');
    }
};