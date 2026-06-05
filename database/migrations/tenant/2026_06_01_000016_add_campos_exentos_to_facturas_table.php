<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('orden_compra_exenta',      60)->nullable()->after('direccion_cliente');
            $table->string('num_constancia_exonerado', 60)->nullable()->after('orden_compra_exenta');
            $table->string('num_registro_sag',         60)->nullable()->after('num_constancia_exonerado');
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn(['orden_compra_exenta', 'num_constancia_exonerado', 'num_registro_sag']);
        });
    }
};
