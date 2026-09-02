<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('retiros', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            // FK al padre (solicitud)
            $table->unsignedBigInteger('solicitud_id')->nullable(false);

            // Fecha propuesta para el retiro
            $table->date('fecha_retiro')->nullable(false);

            // Tipo de retiro, desde catalogos
            $table->unsignedBigInteger('tipo_retiro_id')->nullable(false);

            // Volumen estimado (ej. en m3, litros, etc.)
            $table->decimal('volumen_estimado', 8, 2)->nullable(true);

            // Indica si se marcó como urgente, complementario a tipo_retiro
            $table->boolean('es_urgente')->default(false);

            // Comentarios u observaciones
            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Relaciones
            $table->foreign('solicitud_id')->references('id')->on('solicitudes')->onUpdate('cascade')->onDelete('cascade');
            $table->foreign('tipo_retiro_id')->references('id')->on('catalogos')->onUpdate('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('retiros');
    }
};
