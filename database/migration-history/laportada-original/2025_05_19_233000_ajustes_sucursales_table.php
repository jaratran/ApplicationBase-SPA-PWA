<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {

            // 🔴 Paso 1: Eliminar la foreign key de ciudad_id (si existe)
            $table->dropForeign(['ciudad_id']);

            // 🔴 Paso 2: Eliminar la columna ciudad_id
            $table->dropColumn('ciudad_id');

            // 🟡 Paso 3: Eliminar foreign keys antiguas (zona y tipo) que apuntaban a list_parameters
            $table->dropForeign(['zona_id']);
            $table->dropForeign(['tipo_sucursal_id']);

            // 🟢 Paso 4: Agregar nuevas foreign keys hacia catalogos
            $table->foreign('zona_id')->references('id')->on('catalogos')->onDelete('restrict');
            $table->foreign('tipo_sucursal_id')->references('id')->on('catalogos')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('sucursales', function (Blueprint $table) {

            // 🔁 Paso inverso: agregar columna ciudad_id nuevamente
            $table->unsignedBigInteger('ciudad_id')->nullable();

            // 🔁 Restaurar foreign keys originales
            $table->foreign('zona_id')->references('id')->on('list_parameters')->onDelete('restrict');
            $table->foreign('tipo_sucursal_id')->references('id')->on('list_parameters')->onDelete('restrict');
            $table->foreign('ciudad_id')->references('id')->on('list_parameters')->onDelete('restrict');
        });
    }
};
