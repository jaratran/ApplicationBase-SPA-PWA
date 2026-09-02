<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('planificaciones', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            // FK al retiro que esta planificación ejecutará
            $table->unsignedBigInteger('retiro_id')->unique()->nullable(false);

            // Fecha y hora planificada para ejecutar el retiro
            $table->dateTime('fecha_hora_planificada')->nullable(false);

            // Estado lógico (catálogo o entero libre)
            $table->tinyInteger('estado')->default(0);
            // Ej: 0 = pendiente, 1 = confirmado, 2 = reprogramado, 3 = anulado

            // Observaciones
            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Clave foránea
            $table->foreign('retiro_id')->references('id')->on('retiros')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('planificaciones');
    }
};
