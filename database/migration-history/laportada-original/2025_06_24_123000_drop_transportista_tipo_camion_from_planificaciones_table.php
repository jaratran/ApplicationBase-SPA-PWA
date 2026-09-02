<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            $table->dropForeign(['transportista_id']);
            $table->dropColumn('transportista_id');

            $table->dropForeign(['tipo_camion_id']);
            $table->dropColumn('tipo_camion_id');
        });
    }

    public function down(): void
    {
        Schema::table('planificaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('transportista_id')->after('camion_id');
            $table->foreign('transportista_id')->references('id')->on('empresas');

            $table->unsignedBigInteger('tipo_camion_id')->after('transportista_id');
            $table->foreign('tipo_camion_id')->references('id')->on('catalogos');
        });
    }
};
