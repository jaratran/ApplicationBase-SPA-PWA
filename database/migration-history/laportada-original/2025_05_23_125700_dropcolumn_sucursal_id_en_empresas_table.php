<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Paso Unico: Eliminar campo empresa
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropForeign(['sucursal_id']); // importante: eliminar FK antes
            $table->dropColumn('sucursal_id');
        });
    }

    public function down(): void
    {
        // Esta reversión sólo devuelve el campo al final
        Schema::table('empresas', function (Blueprint $table) {
            $table->unsignedBigInteger('sucursal_id')->nullable()->after('id');
            $table->foreign('sucursal_id')
                  ->references('id')->on('sucursales')
                  ->onDelete('restrict')
                  ->onUpdate('cascade');
        });
    }
};
