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
        Schema::create('sync_queue', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 50);
            $table->string('uuid', 36)->index();
            $table->enum('accion', ['crear', 'actualizar', 'anular']);
            $table->json('datos');
            $table->timestamp('origen_at');

            // Estado de envío
            $table->timestamp('enviado_at')->nullable()->index();
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->text('ultimo_error')->nullable();

            $table->timestamps();

            $table->index(['enviado_at', 'intentos']); // para la consulta de pendientes
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_queue');
    }
};
