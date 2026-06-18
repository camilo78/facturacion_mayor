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
        Schema::create('instances', function (Blueprint $table) {
            // UUID de la instancia (= config('instance.uuid') en el propio nodo)
            $table->string('id', 36)->primary();
            $table->enum('tipo', ['mayor', 'auxiliar']);

            // Empresa a la que pertenece el Auxiliar (null para el Mayor)
            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();

            // Establecimiento físico que sirve (null para el Mayor)
            $table->unsignedBigInteger('establecimiento_id')->nullable();

            $table->string('label');           // p.ej. "Sucursal Norte — Caja 1"
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instances');
    }
};
