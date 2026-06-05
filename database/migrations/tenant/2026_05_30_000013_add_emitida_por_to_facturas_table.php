<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Nullable por compatibilidad con facturas emitidas antes de este módulo
            $table->unsignedBigInteger('emitida_por')->nullable()->after('estado');
            // sesion_caja_id se agrega en el módulo SesionCaja (siguiente etapa)
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn('emitida_por');
        });
    }
};
