<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retiros', function (Blueprint $table) {
            // Agregar campo activo
            $table->boolean('activo')->default(true)->after('volumen_estimado');
        });
    }

    public function down(): void
    {
        Schema::table('retiros', function (Blueprint $table) {
            // Eliminar campo activo
            $table->dropColumn('activo');
        });
    }
};