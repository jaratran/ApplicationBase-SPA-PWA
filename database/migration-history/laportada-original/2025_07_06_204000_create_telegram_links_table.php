<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('telegram_links', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);
            
            $table->foreignId('conductor_id')
                  ->constrained('conductores')
                  ->onDelete('cascade');

            $table->string('pin', 10)->nullable();
            $table->string('chat_id')->nullable();
            
            $table->enum('estado', ['pendiente', 'vinculado', 'revocado'])->default('pendiente');

            $table->timestamp('fecha_generacion')->nullable();
            $table->timestamp('fecha_vinculacion')->nullable();
            $table->integer('intentos')->default(0);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_links');
    }
};
