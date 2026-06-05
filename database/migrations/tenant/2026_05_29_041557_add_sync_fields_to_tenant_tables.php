<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    // Tablas que ya tienen uuid desde su creación; solo necesitan origin y synced_at
    private array $tablesNeedingSync = [
        'establecimientos',
        'puntos_emision',
        'impuestos',
        'cai_autorizaciones',
        'facturas',
        'detalle_facturas',
        'clientes',
        'productos',
    ];

    public function up(): void
    {
        // users nunca tuvo uuid; lo agregamos aquí junto con los campos de sync
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('origin', 36)->nullable()->after('remember_token');
            $table->timestamp('synced_at')->nullable()->after('origin');
        });

        foreach ($this->tablesNeedingSync as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('origin', 36)->nullable()->after('uuid');
                $table->timestamp('synced_at')->nullable()->after('origin');
            });
        }

        // Backfill de uuid en registros existentes de users
        DB::table('users')->whereNull('uuid')->lazyById()->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update(['uuid' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('synced_at');
            $table->dropColumn('origin');
            $table->dropColumn('uuid');
        });

        foreach ($this->tablesNeedingSync as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('synced_at');
                $table->dropColumn('origin');
            });
        }
    }
};
