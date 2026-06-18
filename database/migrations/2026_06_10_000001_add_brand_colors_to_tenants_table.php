<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('color_primario',  7)->default('#1b3a5c')->after('logo');
            $table->string('color_secundario', 7)->default('#009898')->after('color_primario');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['color_primario', 'color_secundario']);
        });
    }
};
