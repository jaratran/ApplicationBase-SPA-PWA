<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->bigIncrements('id')->nullable(false);

            // FK al autor de la solicitud
            $table->unsignedBigInteger('user_id')->nullable(false);

            // FK al contexto de maquila (empresa-sucursal)
            $table->unsignedBigInteger('maquila_id')->nullable(false);

            // Estado lógico de la solicitud
            $table->tinyInteger('estado')->default(0); // Por ejemplo: 0 = borrador, 1 = enviada, 2 = aprobada, etc.

            // Campos auditables
            $table->text('observaciones')->nullable();
            $table->timestamps();

            // Índices y claves foráneas
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade');
            $table->foreign('maquila_id')->references('id')->on('maquilas')->onUpdate('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('solicitudes');
    }
};
