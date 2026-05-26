<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('nombre')->after('id');
            $table->string('nombre_comercial')->nullable()->after('nombre');
            $table->string('rtn', 20)->unique()->after('nombre_comercial');
            $table->string('email')->after('rtn');
            $table->string('telefono')->nullable()->after('email');
            $table->string('plan')->default('basico')->after('telefono');
            $table->string('estado')->default('activo')->after('plan');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['nombre','nombre_comercial','rtn','email','telefono','plan','estado']);
        });
    }
};
