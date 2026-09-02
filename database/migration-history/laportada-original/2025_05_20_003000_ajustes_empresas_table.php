<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // 🔴 Paso 1: Eliminar la antigua foreign key hacia list_parameters
            $table->dropForeign(['tipo_empresa_id']);
        });

        // 🔁 Paso 2: Ajustar los valores de tipo_empresa_id para alinearlos con catalogos categoría 509
        // Suponemos que el ID antiguo coincide con el ID de list_parameters, y que en catalogos
        // se han creado los nuevos con ID = ID antiguo - 509
        DB::statement('UPDATE empresas SET tipo_empresa_id = tipo_empresa_id - 509');

        Schema::table('empresas', function (Blueprint $table) {
            // 🟢 Paso 3: Crear la nueva foreign key apuntando a catalogos
            $table->foreign('tipo_empresa_id')->references('id')->on('catalogos')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            // 🔁 Revertimos la foreign key hacia catalogos
            $table->dropForeign(['tipo_empresa_id']);
        });

        // 🔁 Revertimos los valores de vuelta a list_parameters (509 de diferencia)
        DB::statement('UPDATE empresas SET tipo_empresa_id = tipo_empresa_id + 509');

        Schema::table('empresas', function (Blueprint $table) {
            // 🔁 Restauramos la foreign key original hacia list_parameters
            $table->foreign('tipo_empresa_id')->references('id')->on('list_parameters')->onDelete('restrict');
        });
    }
};
