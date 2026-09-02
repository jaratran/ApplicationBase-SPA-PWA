<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('camiones', function (Blueprint $table) {
            // 🔴 Paso 1: Eliminar la foreign key antigua
            $table->dropForeign(['tipo_camion_id']);
        });

        // 🔁 Paso 2: Actualizar valores para que coincidan con los IDs de catalogos
        DB::statement('UPDATE camiones SET tipo_camion_id = tipo_camion_id - 517');

        Schema::table('camiones', function (Blueprint $table) {
            // 🟢 Paso 3: Crear nueva foreign key apuntando a catalogos
            $table->foreign('tipo_camion_id')->references('id')->on('catalogos')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('camiones', function (Blueprint $table) {
            // 🔁 Revertir FK hacia catalogos
            $table->dropForeign(['tipo_camion_id']);
        });

        // 🔁 Revertir IDs a los originales de list_parameters
        DB::statement('UPDATE camiones SET tipo_camion_id = tipo_camion_id + 517');

        Schema::table('camiones', function (Blueprint $table) {
            // 🔁 Restaurar FK a list_parameters
            $table->foreign('tipo_camion_id')->references('id')->on('list_parameters')->onDelete('restrict');
        });
    }
};
