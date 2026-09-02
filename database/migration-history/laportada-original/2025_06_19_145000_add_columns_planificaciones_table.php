<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {

            // Insertar después de 'fecha_hora_planificada'
            $table->string('duracion_viaje', 5)->after('fecha_hora_planificada');
            $table->dateTime('hora_llegada_estimada')->after('duracion_viaje');
            $table->unsignedBigInteger('especie_id')->after('hora_llegada_estimada');
            $table->boolean('tiene_restriccion')->default(false)->after('especie_id');
            $table->unsignedBigInteger('tipo_materia_prima_id')->after('tiene_restriccion');
            $table->unsignedBigInteger('camion_id')->after('tipo_materia_prima_id');
            $table->string('patente_rampla', 20)->nullable()->after('camion_id');
            $table->integer('cantidad_bins_reponer')->default(0)->after('patente_rampla');
            $table->unsignedBigInteger('transportista_id')->after('cantidad_bins_reponer');
            $table->unsignedBigInteger('tipo_camion_id')->after('transportista_id');
            $table->unsignedBigInteger('conductor_id')->after('tipo_camion_id');
            $table->unsignedBigInteger('estado_id')->after('conductor_id');

            // Eliminar campo 'estado'
            $table->dropColumn('estado');

            // Insertar después de 'observaciones'
            $table->boolean('activo')->default(true)->after('observaciones');

            // Definir claves foráneas
            $table->foreign('especie_id')->references('id')->on('catalogos')->onDelete('restrict');
            $table->foreign('tipo_materia_prima_id')->references('id')->on('catalogos')->onDelete('restrict');
            $table->foreign('camion_id')->references('id')->on('camiones')->onDelete('restrict');
            $table->foreign('transportista_id')->references('id')->on('empresas')->onDelete('restrict');
            $table->foreign('tipo_camion_id')->references('id')->on('catalogos')->onDelete('restrict');
            $table->foreign('conductor_id')->references('id')->on('conductores')->onDelete('restrict');
            $table->foreign('estado_id')->references('id')->on('catalogos')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {

            // Revertir los cambios
            $table->dropForeign(['especie_id']);
            $table->dropForeign(['tipo_materia_prima_id']);
            $table->dropForeign(['camion_id']);
            $table->dropForeign(['transportista_id']);
            $table->dropForeign(['tipo_camion_id']);
            $table->dropForeign(['conductor_id']);
            $table->dropForeign(['estado_id']);

            $table->dropColumn([
                'duracion_viaje',
                'hora_llegada_estimada',
                'especie_id',
                'tiene_restriccion',
                'tipo_materia_prima_id',
                'camion_id',
                'patente_rampla',
                'cantidad_bins_reponer',
                'transportista_id',
                'tipo_camion_id',
                'conductor_id',
                'estado_id',
                'activo',
            ]);

            $table->tinyInteger('estado')->default(0)->after('fecha_hora_planificada');
        });
    }
};
