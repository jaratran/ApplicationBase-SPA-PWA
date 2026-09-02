<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('retiros_comentarios', function (Blueprint $table) {
            // PK
            $table->bigIncrements('id')->nullable(false);

            // FK al retiro
            $table->unsignedBigInteger('retiro_id')->nullable(false);

            // Comentario asociado al retiro
            $table->text('comentario')->nullable();

            // Usuario que realiza el comentario
            $table->unsignedBigInteger('usuario_id')->nullable(false);

            // Fecha de creación (solo created_at, sin updated_at)
            $table->timestamp('created_at')->useCurrent();

            // Relaciones
            $table->foreign('retiro_id')
                  ->references('id')->on('retiros')
                  ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('usuario_id')
                  ->references('id')->on('users')
                  ->onUpdate('cascade');

            // Índice útil para búsqueda por retiro
            $table->index('retiro_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retiros_comentarios');
    }
};
