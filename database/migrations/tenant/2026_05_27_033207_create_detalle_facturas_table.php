<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detalle_facturas', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('factura_id')->constrained('facturas')->cascadeOnDelete();
            $table->string('descripcion');
            $table->decimal('cantidad', 12, 3)->default(1);
            $table->decimal('precio_unitario', 14, 2);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->string('impuesto_codigo', 20);     // snapshot: EXENTO, ISV15...
            $table->decimal('impuesto_tasa', 5, 2);     // snapshot de la tasa
            $table->decimal('subtotal', 14, 2);         // base: cantidad*precio - descuento
            $table->decimal('isv', 14, 2)->default(0);  // impuesto del renglón
            $table->decimal('total', 14, 2);            // subtotal + isv
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_facturas');
    }
};