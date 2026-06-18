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
        Schema::create('instance_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('instance_id', 36);
            $table->foreign('instance_id')->references('id')->on('instances')->cascadeOnDelete();

            // sha256 del token plano; el texto plano solo se muestra una vez al registrar
            $table->string('token_hash', 64)->unique();

            $table->boolean('revocado')->default(false);
            $table->timestamp('ultimo_uso_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instance_tokens');
    }
};
