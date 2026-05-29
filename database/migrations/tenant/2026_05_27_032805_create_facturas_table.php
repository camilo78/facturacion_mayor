<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('facturas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            $table->foreignId('cai_autorizacion_id')->constrained('cai_autorizaciones');
            $table->foreignId('punto_emision_id')->constrained('puntos_emision');

            // Componentes del número (snapshot, inmutables)
            $table->string('establecimiento_codigo', 3);
            $table->string('punto_emision_codigo', 3);
            $table->string('tipo_documento', 2);
            $table->unsignedInteger('correlativo');
            $table->string('numero_completo', 20)->unique();  // NNN-NNN-NN-NNNNNNNN
            $table->string('cai');                            // snapshot del código CAI

            // Cliente (snapshot; consumidor final permitido)
            $table->string('rtn_cliente', 20)->nullable();
            $table->string('nombre_cliente')->default('Consumidor Final');

            // Montos
            $table->decimal('subtotal_exento', 14, 2)->default(0);
            $table->decimal('subtotal_gravado', 14, 2)->default(0);
            $table->decimal('total_isv', 14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);

            $table->string('tipo_pago')->default('contado');  // contado | credito
            $table->string('estado')->default('VIGENTE');     // VIGENTE | ANULADA

            // Anulación (auditable)
            $table->string('motivo_anulacion')->nullable();
            $table->unsignedBigInteger('anulada_por')->nullable();
            $table->timestamp('anulada_at')->nullable();

            $table->timestamp('fecha_emision');
            $table->timestamps();

            $table->index(['punto_emision_id', 'tipo_documento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('facturas');
    }
};