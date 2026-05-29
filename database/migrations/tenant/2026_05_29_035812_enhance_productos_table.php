<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->string('codigo_barras', 30)->nullable()->after('codigo')->index();
            $table->text('descripcion_larga')->nullable()->after('descripcion');
            $table->string('tipo')->default('bien')->after('descripcion_larga');         // bien | servicio
            $table->string('categoria')->nullable()->after('tipo')->index();
            $table->string('marca')->nullable()->after('categoria');
            $table->string('unidad_medida', 20)->default('unidad')->after('marca');
            $table->decimal('precio_compra', 14, 2)->nullable()->after('precio_unitario');
            $table->boolean('incluye_isv')->default(false)->after('impuesto_id');
            $table->boolean('controla_inventario')->default(false)->after('incluye_isv');
            $table->boolean('precio_editable_en_emision')->default(true)->after('controla_inventario');
            $table->text('notas')->nullable()->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropColumn([
                'codigo_barras', 'descripcion_larga', 'tipo', 'categoria', 'marca',
                'unidad_medida', 'precio_compra', 'incluye_isv', 'controla_inventario',
                'precio_editable_en_emision', 'notas',
            ]);
        });
    }
};