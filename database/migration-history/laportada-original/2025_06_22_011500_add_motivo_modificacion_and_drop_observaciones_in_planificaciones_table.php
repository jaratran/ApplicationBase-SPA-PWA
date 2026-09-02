<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            // Agregamos la nueva columna motivo_modificacion_id
            $table->unsignedBigInteger('motivo_modificacion_id')
                  ->after('conductor_id');

            $table->foreign('motivo_modificacion_id')
                  ->references('id')
                  ->on('catalogos');

            // Eliminamos la columna observaciones
            $table->dropColumn('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            // Restauramos la columna observaciones
            $table->text('observaciones')->nullable();

            // Eliminamos la nueva columna motivo_modificacion_id
            $table->dropForeign(['motivo_modificacion_id']);
            $table->dropColumn('motivo_modificacion_id');
        });
    }
};
