<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('retiros_historial', function (Blueprint $table) {
            // Cambiar tipo de dato de kilogramos_estimados a integer
            $table->integer('kilogramos_estimados')->change();
        });
    }

    public function down(): void
    {
        Schema::table('retiros_historial', function (Blueprint $table) {
            // Revertir a decimal(8,2) en rollback
            $table->decimal('kilogramos_estimados', 8, 2)->change();
        });
    }
};
