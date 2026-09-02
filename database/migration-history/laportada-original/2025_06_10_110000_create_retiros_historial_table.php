<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('retiros_historial', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            // FK al padre (retiro)
            $table->unsignedBigInteger('retiro_id')->nullable(false);

            // Campos clonados de retiros (misma estructura)
            $table->dateTime('fecha_retiro');
            $table->unsignedBigInteger('tipo_retiro_id');
            $table->decimal('kilogramos_estimados', 8, 2);
            $table->boolean('requiere_reposicion')->default(false);
            $table->unsignedInteger('cantidad_bins')->nullable();
            $table->unsignedBigInteger('estado_id')->nullable();
            $table->boolean('activo')->default(true);

            // Sólo create porque este registro es para historial
            $table->timestamp('created_at')->useCurrent();
            // Quién hizo el cambio
            $table->unsignedBigInteger('usuario_id')->nullable(false);
            // Porqué se hizo el cambio
            $table->text('motivo_cambio')->nullable();

            // Relaciones
            $table->foreign('retiro_id')->references('id')->on('retiros')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('tipo_retiro_id')->references('id')->on('catalogos')->onUpdate('cascade');
            $table->foreign('estado_id')->references('id')->on('catalogos')->onUpdate('cascade');
            $table->foreign('usuario_id')->references('id')->on('users')->onUpdate('cascade');

            // Índice para búsquedas por retiro
            $table->index('retiro_id');            
        });
    }

    public function down(): void {
        Schema::dropIfExists('retiros_historial');
    }
};
